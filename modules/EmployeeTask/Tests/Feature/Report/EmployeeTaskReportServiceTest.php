<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\Report;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\EmployeeTask\Services\EmployeeTaskReportService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Feature tests for EmployeeTaskReportService::getIntraDayReport().
 *
 * Invariants under test:
 *  - Report structure always contains required keys.
 *  - task_sessions maps to completed/in_progress/paused tasks on the given date.
 *  - active_task is populated only when a task is in an active status.
 *  - summary totals are formatted as HH:MM strings (INV-16 convention).
 *  - total_work_hours = attendance_hours + task_hours.
 *  - Tasks on a different date are NOT included.
 *
 * @group requires-db
 */
final class EmployeeTaskReportServiceTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Company $company;
    private EmployeeTaskReportService $service;
    private string $today;
    private string $tz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['status' => 'active']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->service = $this->app->make(EmployeeTaskReportService::class);
        $this->tz      = 'Asia/Riyadh';
        $this->today   = CarbonImmutable::now($this->tz)->toDateString();
    }

    // -------------------------------------------------------------------------
    // Structure
    // -------------------------------------------------------------------------

    public function test_report_returns_required_top_level_keys(): void
    {
        $report = $this->service->getIntraDayReport($this->user->id, $this->today);

        $this->assertArrayHasKey('data', $report);
        $data = $report['data'];

        $this->assertArrayHasKey('date',                $data);
        $this->assertArrayHasKey('user_id',             $data);
        $this->assertArrayHasKey('attendance_sessions', $data);
        $this->assertArrayHasKey('task_sessions',       $data);
        $this->assertArrayHasKey('active_task',         $data);
        $this->assertArrayHasKey('summary',             $data);
    }

    public function test_summary_contains_required_hour_keys(): void
    {
        $report  = $this->service->getIntraDayReport($this->user->id, $this->today);
        $summary = $report['data']['summary'];

        $this->assertArrayHasKey('attendance_total_hours', $summary);
        $this->assertArrayHasKey('task_total_hours',       $summary);
        $this->assertArrayHasKey('total_work_hours',       $summary);
    }

    public function test_summary_hour_fields_are_hhmm_formatted(): void
    {
        $report  = $this->service->getIntraDayReport($this->user->id, $this->today);
        $summary = $report['data']['summary'];

        foreach (['attendance_total_hours', 'task_total_hours', 'total_work_hours'] as $key) {
            $this->assertMatchesRegularExpression(
                '/^\d{2}:\d{2}$/',
                $summary[$key],
                "INV-16: {$key} must be HH:MM format"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Task sessions
    // -------------------------------------------------------------------------

    public function test_completed_task_on_today_appears_in_task_sessions(): void
    {
        $now = CarbonImmutable::now($this->tz);

        $task = EmployeeTaskRequest::create([
            'company_id'       => $this->company->id,
            'user_id'          => $this->user->id,
            'serial_number'    => 'TASK-RPT-' . uniqid(),
            'title'            => 'Completed task',
            'duration_hours'   => 2,
            'task_date'        => $this->today,
            'task_latitude'    => 24.7136,
            'task_longitude'   => 46.6753,
            'status'           => 'completed',
            'time_from'        => $now->subHours(2)->format('Y-m-d H:i:s'),
            'time_to'          => $now->format('Y-m-d H:i:s'),
            'total_task_hours' => 2.0,
            'shift_end_method' => 'manual',
            'timezone'         => $this->tz,
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);
        $ids    = array_column($report['data']['task_sessions'], 'task_id');

        $this->assertContains($task->id, $ids);
    }

    public function test_in_progress_task_on_today_appears_in_task_sessions(): void
    {
        $now = CarbonImmutable::now($this->tz);

        $task = EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-RPT2-' . uniqid(),
            'title'          => 'In-progress task',
            'duration_hours' => 4,
            'task_date'      => $this->today,
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHour()->format('Y-m-d H:i:s'),
            'timezone'       => $this->tz,
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);
        $ids    = array_column($report['data']['task_sessions'], 'task_id');

        $this->assertContains($task->id, $ids);
    }

    public function test_pending_task_is_not_included_in_task_sessions(): void
    {
        EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-RPT3-' . uniqid(),
            'title'          => 'Pending task',
            'duration_hours' => 4,
            'task_date'      => $this->today,
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'pending',
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);

        $statuses = array_column($report['data']['task_sessions'], 'status');
        $this->assertNotContains('pending', $statuses);
    }

    public function test_task_on_different_date_is_not_included(): void
    {
        $yesterday = CarbonImmutable::now($this->tz)->subDay()->toDateString();

        $task = EmployeeTaskRequest::create([
            'company_id'       => $this->company->id,
            'user_id'          => $this->user->id,
            'serial_number'    => 'TASK-RPT4-' . uniqid(),
            'title'            => 'Yesterday task',
            'duration_hours'   => 2,
            'task_date'        => $yesterday,
            'task_latitude'    => 24.7136,
            'task_longitude'   => 46.6753,
            'status'           => 'completed',
            'total_task_hours' => 2.0,
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);
        $ids    = array_column($report['data']['task_sessions'], 'task_id');

        $this->assertNotContains($task->id, $ids);
    }

    // -------------------------------------------------------------------------
    // active_task
    // -------------------------------------------------------------------------

    public function test_active_task_is_null_when_no_active_tasks(): void
    {
        $report = $this->service->getIntraDayReport($this->user->id, $this->today);

        $this->assertNull($report['data']['active_task']);
    }

    public function test_active_task_is_populated_for_in_progress_task(): void
    {
        $now = CarbonImmutable::now($this->tz);

        EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-ACT-' . uniqid(),
            'title'          => 'Active task',
            'duration_hours' => 4,
            'task_date'      => $this->today,
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHour()->format('Y-m-d H:i:s'),
            'timezone'       => $this->tz,
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);

        $this->assertNotNull($report['data']['active_task']);
        $active = $report['data']['active_task'];

        $this->assertArrayHasKey('task_id',                   $active);
        $this->assertArrayHasKey('elapsed_seconds',           $active);
        $this->assertArrayHasKey('remaining_seconds',         $active);
        $this->assertArrayHasKey('progress_percentage',       $active);
        $this->assertArrayHasKey('can_request_extension',     $active);
        $this->assertArrayHasKey('elapsed_formatted',         $active);
        $this->assertArrayHasKey('remaining_formatted',       $active);
    }

    public function test_active_task_elapsed_formatted_is_hhmmss(): void
    {
        $now = CarbonImmutable::now($this->tz);

        EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-FMT-' . uniqid(),
            'title'          => 'Formatted task',
            'duration_hours' => 4,
            'task_date'      => $this->today,
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHour()->format('Y-m-d H:i:s'),
            'timezone'       => $this->tz,
        ]);

        $report  = $this->service->getIntraDayReport($this->user->id, $this->today);
        $active  = $report['data']['active_task'];

        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $active['elapsed_formatted']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}:\d{2}$/', $active['remaining_formatted']);
    }

    // -------------------------------------------------------------------------
    // task_session structure
    // -------------------------------------------------------------------------

    public function test_task_session_entry_contains_required_fields(): void
    {
        $now = CarbonImmutable::now($this->tz);

        EmployeeTaskRequest::create([
            'company_id'       => $this->company->id,
            'user_id'          => $this->user->id,
            'serial_number'    => 'TASK-STRUCT-' . uniqid(),
            'title'            => 'Struct test task',
            'duration_hours'   => 2,
            'task_date'        => $this->today,
            'task_latitude'    => 24.7136,
            'task_longitude'   => 46.6753,
            'status'           => 'completed',
            'time_from'        => $now->subHours(2)->format('Y-m-d H:i:s'),
            'time_to'          => $now->format('Y-m-d H:i:s'),
            'total_task_hours' => 2.0,
            'shift_end_method' => 'manual',
            'timezone'         => $this->tz,
        ]);

        $report  = $this->service->getIntraDayReport($this->user->id, $this->today);
        $session = $report['data']['task_sessions'][0] ?? null;

        $this->assertNotNull($session);

        $requiredKeys = ['type', 'task_id', 'serial_number', 'title', 'time_from', 'time_to',
                         'total_task_hours', 'status', 'status_label', 'task_location', 'work_sessions'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $session, "Missing key: {$key}");
        }

        $this->assertSame('task', $session['type']);
    }

    // -------------------------------------------------------------------------
    // User isolation
    // -------------------------------------------------------------------------

    public function test_report_only_returns_tasks_for_the_specified_user(): void
    {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);
        $now       = CarbonImmutable::now($this->tz);

        $myTask = EmployeeTaskRequest::create([
            'company_id'       => $this->company->id,
            'user_id'          => $this->user->id,
            'serial_number'    => 'TASK-ME-' . uniqid(),
            'title'            => 'My task',
            'duration_hours'   => 2,
            'task_date'        => $this->today,
            'task_latitude'    => 24.7136,
            'task_longitude'   => 46.6753,
            'status'           => 'completed',
            'time_from'        => $now->subHours(2)->format('Y-m-d H:i:s'),
            'time_to'          => $now->format('Y-m-d H:i:s'),
            'total_task_hours' => 2.0,
            'shift_end_method' => 'manual',
        ]);

        $theirTask = EmployeeTaskRequest::create([
            'company_id'       => $this->company->id,
            'user_id'          => $otherUser->id,
            'serial_number'    => 'TASK-THEM-' . uniqid(),
            'title'            => 'Their task',
            'duration_hours'   => 2,
            'task_date'        => $this->today,
            'task_latitude'    => 24.7136,
            'task_longitude'   => 46.6753,
            'status'           => 'completed',
            'time_from'        => $now->subHours(2)->format('Y-m-d H:i:s'),
            'time_to'          => $now->format('Y-m-d H:i:s'),
            'total_task_hours' => 2.0,
            'shift_end_method' => 'manual',
        ]);

        $report = $this->service->getIntraDayReport($this->user->id, $this->today);
        $ids    = array_column($report['data']['task_sessions'], 'task_id');

        $this->assertContains($myTask->id, $ids);
        $this->assertNotContains($theirTask->id, $ids);
    }
}

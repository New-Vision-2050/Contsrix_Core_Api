<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\Report;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Country\Models\Country;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Feature tests for the admin employee-tasks report endpoints:
 *  - GET /api/v1/admin/employee-tasks/report        (paginated table + filters)
 *  - GET /api/v1/admin/employee-tasks/report/{id}   (full lifecycle detail)
 *
 * Invariants under test:
 *  - List rows expose employee, type, dates, location, and final status.
 *  - Filters: user_id, status, task_date, date_from/date_to, search.
 *  - Detail exposes locations, work sessions, and a chronological
 *    `processes` timeline (create/start/end/approval/extension) where each
 *    process carries its step chain with who accepted/rejected and when.
 *  - Unknown task id returns the standard error envelope with code 404.
 *
 * @group requires-db
 */
final class EmployeeTaskReportEndpointTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;
    private User $admin;
    private User $employee;
    private User $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $country = Country::query()->first();
        if (! $country) {
            $this->markTestSkipped('No seeded country available for company creation.');
        }

        $this->company = Company::withoutEvents(fn () => Company::query()->create([
            'id'                   => (string) Str::uuid(),
            'name'                 => ['en' => 'Task Report Company'],
            'user_name'            => 'task_report_' . Str::random(6),
            'email'                => 'task-report-' . Str::random(6) . '@example.test',
            'phone'                => '01000000000',
            'country_id'           => $country->id,
            'company_type_id'      => (string) Str::uuid(),
            'company_field_id'     => (string) Str::uuid(),
            'registration_type_id' => (string) Str::uuid(),
            'general_manager_id'   => (string) Str::uuid(),
            'is_active'            => 1,
            'complete_data'        => 1,
            'serial_no'            => 'TASK-REPORT-' . Str::upper(Str::random(8)),
        ]));
        $this->company->domains()->firstOrCreate(['domain' => 'task-report-' . Str::random(6) . '.test']);
        tenancy()->initialize($this->company);

        $this->admin         = User::factory()->create(['company_id' => $this->company->id]);
        $this->employee      = User::factory()->create(['company_id' => $this->company->id]);
        $this->otherEmployee = User::factory()->create(['company_id' => $this->company->id]);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function createTask(array $overrides = []): EmployeeTaskRequest
    {
        return EmployeeTaskRequest::create(array_merge([
            'company_id'     => $this->company->id,
            'user_id'        => $this->employee->id,
            'serial_number'  => 'TASK-REPORT-' . Str::upper(Str::random(6)),
            'title'          => 'Report test task',
            'duration_hours' => 4,
            'task_date'      => CarbonImmutable::now('Asia/Riyadh')->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'radius_meters'  => 150,
            'status'         => 'approved',
        ], $overrides));
    }

    private function getReport(array $query = [])
    {
        return $this->actingAs($this->admin, 'api')
            ->withHeader('X-Tenant', (string) $this->company->id)
            ->getJson('/api/v1/admin/employee-tasks/report' . ($query ? '?' . http_build_query($query) : ''));
    }

    private function getReportDetail(string $id)
    {
        return $this->actingAs($this->admin, 'api')
            ->withHeader('X-Tenant', (string) $this->company->id)
            ->getJson("/api/v1/admin/employee-tasks/report/{$id}");
    }

    // ---------------------------------------------------------------------
    // List endpoint
    // ---------------------------------------------------------------------

    public function test_report_list_returns_paginated_rows_with_expected_shape(): void
    {
        $this->createTask();
        $this->createTask();

        $response = $this->getReport();

        $response->assertOk()
            ->assertJsonStructure([
                'payload' => [
                    '*' => [
                        'id',
                        'serial_number',
                        'title',
                        'employee' => ['id', 'name'],
                        'task_date',
                        'duration_hours',
                        'status',
                        'status_label',
                        'final_status',
                        'final_status_label',
                        'task_location' => ['latitude', 'longitude', 'radius_meters'],
                        'created_at',
                    ],
                ],
                'pagination' => ['page', 'next_page', 'last_page', 'result_count'],
            ]);

        $this->assertSame(2, $response->json('pagination.result_count'));
    }

    public function test_report_list_filters_by_employee(): void
    {
        $mine  = $this->createTask();
        $this->createTask(['user_id' => $this->otherEmployee->id]);

        $response = $this->getReport(['user_id' => (string) $this->employee->id]);

        $response->assertOk();
        $ids = collect($response->json('payload'))->pluck('id');

        $this->assertContains((string) $mine->id, $ids);
        $this->assertSame(1, $ids->count());
    }

    public function test_report_list_filters_by_date_range(): void
    {
        $inside  = $this->createTask(['task_date' => '2026-09-10']);
        $outside = $this->createTask(['task_date' => '2026-08-01']);

        $response = $this->getReport(['date_from' => '2026-09-01', 'date_to' => '2026-09-30']);

        $response->assertOk();
        $ids = collect($response->json('payload'))->pluck('id');

        $this->assertContains((string) $inside->id, $ids);
        $this->assertNotContains((string) $outside->id, $ids);
    }

    public function test_report_list_filters_by_status(): void
    {
        $completed = $this->createTask(['status' => 'completed']);
        $this->createTask(['status' => 'pending']);

        $response = $this->getReport(['status' => 'completed']);

        $response->assertOk();
        $ids = collect($response->json('payload'))->pluck('id');

        $this->assertContains((string) $completed->id, $ids);
        $this->assertSame(1, $ids->count());
    }

    public function test_report_list_search_matches_serial_number(): void
    {
        $match = $this->createTask(['serial_number' => 'TASK-REPORT-NEEDLE']);
        $this->createTask(['serial_number' => 'TASK-REPORT-OTHER']);

        $response = $this->getReport(['search' => 'NEEDLE']);

        $response->assertOk();
        $ids = collect($response->json('payload'))->pluck('id');

        $this->assertContains((string) $match->id, $ids);
        $this->assertSame(1, $ids->count());
    }

    // ---------------------------------------------------------------------
    // Detail endpoint
    // ---------------------------------------------------------------------

    public function test_report_detail_returns_full_lifecycle_with_process_steps(): void
    {
        $task = $this->createTask([
            'status'      => 'completed',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'time_from'   => now()->subHours(5),
            'time_to'     => now()->subHour(),
        ]);

        // --- Create-task approval process with a full step chain -----------
        $setting = ProcedureSetting::create([
            'name'         => 'Employee Task Approval',
            'type'         => 'employee_task',
            'execute_type' => 'sequence',
            'company_id'   => $this->company->id,
            'form'         => 'createTask',
            'is_active'    => true,
        ]);

        $stepOne = ProcedureSettingStep::create([
            'procedure_setting_id' => $setting->id,
            'company_id'           => $this->company->id,
            'name'                 => 'Manager Approval',
            'is_approve'           => true,
        ]);

        $stepTwo = ProcedureSettingStep::create([
            'procedure_setting_id' => $setting->id,
            'company_id'           => $this->company->id,
            'name'                 => 'HR Review',
            'is_approve'           => true,
        ]);

        $createProcess = Process::create([
            'processable_id'       => $task->id,
            'processable_type'     => 'employee_task',
            'execute_type'         => 'sequence',
            'status'               => ProcessStatus::Completed->value,
            'sort_order'           => 1,
            'procedure_setting_id' => $setting->id,
        ]);

        ProcessStep::create([
            'process_id'          => $createProcess->id,
            'step_id'             => $stepOne->id,
            'template_step_order' => 1,
            'assigned_user_id'    => $this->admin->id,
            'status'              => ProcessStepStatus::Approved->value,
            'action_by'           => $this->admin->id,
            'acted_at'            => now()->subHours(6),
        ]);

        ProcessStep::create([
            'process_id'          => $createProcess->id,
            'step_id'             => $stepTwo->id,
            'template_step_order' => 2,
            'assigned_user_id'    => $this->otherEmployee->id,
            'status'              => ProcessStepStatus::Approved->value,
            'action_by'           => $this->otherEmployee->id,
            'acted_at'            => now()->subHours(5)->subMinutes(30),
        ]);

        // --- Start request with its own process ----------------------------
        $startProcess = Process::create([
            'processable_id'   => $task->id,
            'processable_type' => 'employee_task',
            'execute_type'     => 'sequence',
            'status'           => ProcessStatus::Completed->value,
            'sort_order'       => 2,
        ]);

        ProcessStep::create([
            'process_id'          => $startProcess->id,
            'template_step_order' => 1,
            'assigned_user_id'    => $this->admin->id,
            'status'              => ProcessStepStatus::Approved->value,
            'action_by'           => $this->admin->id,
            'acted_at'            => now()->subHours(5),
        ]);

        EmployeeTaskStartRequest::create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $this->company->id,
            'process_id'               => $startProcess->id,
            'requested_by'             => $this->employee->id,
            'latitude'                 => 24.7140,
            'longitude'                => 46.6760,
            'status'                   => 'approved',
            'reviewed_by'              => $this->admin->id,
            'reviewed_at'              => now()->subHours(5),
        ]);

        // --- End request ----------------------------------------------------
        EmployeeTaskEndRequest::create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $this->company->id,
            'requested_by'             => $this->employee->id,
            'latitude'                 => 24.7150,
            'longitude'                => 46.6770,
            'status'                   => 'approved',
            'reviewed_by'              => $this->admin->id,
            'reviewed_at'              => now()->subHour(),
        ]);

        // --- Completion approval request ------------------------------------
        EmployeeTaskApprovalRequest::create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $this->company->id,
            'requested_by'             => $this->employee->id,
            'status'                   => 'approved',
            'reviewed_by'              => $this->admin->id,
            'reviewed_at'              => now()->subMinutes(30),
        ]);

        // --- Extension request (rejected) ------------------------------------
        EmployeeTaskExtensionRequest::create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $this->company->id,
            'requested_by'             => $this->employee->id,
            'additional_hours'         => 2,
            'reason'                   => 'Need more time',
            'status'                   => 'rejected',
            'reviewed_by'              => $this->admin->id,
            'reviewed_at'              => now()->subHours(2),
            'review_notes'             => 'Not justified',
        ]);

        // --- Work session -----------------------------------------------------
        EmployeeTaskSession::create([
            'employee_task_request_id' => $task->id,
            'company_id'               => $this->company->id,
            'start_time'               => now()->subHours(5),
            'end_time'                 => now()->subHour(),
            'duration_minutes'         => 240,
            'start_latitude'           => 24.7140,
            'start_longitude'          => 46.6760,
            'end_latitude'             => 24.7150,
            'end_longitude'            => 46.6770,
        ]);

        $response = $this->getReportDetail((string) $task->id);

        $response->assertOk()
            ->assertJsonPath('payload.id', (string) $task->id)
            ->assertJsonPath('payload.final_status', 'completed')
            ->assertJsonPath('payload.approved_by.id', (string) $this->admin->id)
            ->assertJsonStructure([
                'payload' => [
                    'locations' => ['task_location', 'start_location', 'end_location'],
                    'work_sessions' => [
                        '*' => ['id', 'start_time', 'end_time', 'duration_minutes', 'start_location', 'end_location'],
                    ],
                    'processes' => [
                        '*' => ['type', 'type_label', 'status', 'requested_by', 'requested_at', 'steps'],
                    ],
                ],
            ]);

        $processes = collect($response->json('payload.processes'));
        $types     = $processes->pluck('type')->all();

        // All five lifecycle procedure kinds are represented.
        foreach (['create', 'start', 'end', 'approval', 'extension'] as $expectedType) {
            $this->assertContains($expectedType, $types, "Missing process type: {$expectedType}");
        }

        // Create process exposes the full accept chain: two approved steps
        // with the acting user and timestamp on each.
        $create = $processes->firstWhere('type', 'create');
        $this->assertSame('approved', $create['status']);
        $this->assertCount(2, $create['steps']);
        $this->assertSame('Manager Approval', $create['steps'][0]['name']);
        $this->assertSame('approved', $create['steps'][0]['status']);
        $this->assertSame((string) $this->admin->id, $create['steps'][0]['action_by']['id']);
        $this->assertNotNull($create['steps'][0]['acted_at']);
        $this->assertSame((string) $this->otherEmployee->id, $create['steps'][1]['action_by']['id']);

        // Start request carries its own process steps + requester location.
        $start = $processes->firstWhere('type', 'start');
        $this->assertSame('approved', $start['status']);
        $this->assertCount(1, $start['steps']);
        $this->assertSame((string) $this->admin->id, $start['steps'][0]['action_by']['id']);
        $this->assertSame(24.714, round((float) $start['location']['latitude'], 3));

        // Extension request surfaces rejection review info.
        $extension = $processes->firstWhere('type', 'extension');
        $this->assertSame('rejected', $extension['status']);
        $this->assertSame('Not justified', $extension['review_notes']);
        $this->assertSame((string) $this->admin->id, $extension['reviewed_by']['id']);

        // Timeline is sorted chronologically by requested_at.
        $requestedAts = $processes->pluck('requested_at')->filter()->all();
        $sorted = $requestedAts;
        sort($sorted);
        $this->assertSame($sorted, array_values($requestedAts));

        // Work session is present with locations.
        $this->assertSame(240, $response->json('payload.work_sessions.0.duration_minutes'));
    }

    public function test_report_detail_returns_error_envelope_for_unknown_task(): void
    {
        $response = $this->getReportDetail((string) Str::uuid());

        // Json::error uses HTTP 200 with status='error' and the exception code
        // embedded in the message payload (project-wide convention).
        $response->assertOk()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message.code', 404);
    }
}

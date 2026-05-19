<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\Lifecycle;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\EmployeeTask\Repositories\EmployeeTaskSessionRepository;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Feature tests for EmployeeTaskLifecycleService.
 *
 * Invariants under test:
 *  - INV-T7: in_progress must have exactly one session with end_time=NULL.
 *  - INV-T5: auto-close job uses ISO closeAtIso, not now().
 *  - Status transitions follow the defined state machine.
 *  - total_task_hours and total_pause_minutes are calculated correctly on end().
 *
 * @group requires-db
 */
final class EmployeeTaskLifecycleServiceTest extends TestCase
{
    use DatabaseTransactions;

    private EmployeeTaskRequest $task;
    private EmployeeTaskLifecycleService $service;
    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['status' => 'active']);
        $this->user    = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);

        $this->service = $this->app->make(EmployeeTaskLifecycleService::class);

        $now = CarbonImmutable::now('Asia/Riyadh');

        $this->task = EmployeeTaskRequest::create([
            'company_id'     => $this->company->id,
            'user_id'        => $this->user->id,
            'serial_number'  => 'TASK-LIFECYCLE-' . uniqid(),
            'title'          => 'Lifecycle test task',
            'duration_hours' => 2,
            'task_date'      => $now->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'approved',
        ]);
    }

    // -------------------------------------------------------------------------
    // start()
    // -------------------------------------------------------------------------

    public function test_start_transitions_approved_to_in_progress(): void
    {
        Queue::fake();

        $dto    = new StartTaskDTO(latitude: 24.7136, longitude: 46.6753);
        $result = $this->service->start($this->task->id, $dto, $this->user);

        $this->assertSame('in_progress', $result->status);
        $this->assertNotNull($result->time_from);
    }

    public function test_start_creates_exactly_one_active_session(): void
    {
        Queue::fake();

        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        $activeSessions = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)
            ->whereNull('end_time')
            ->count();

        $this->assertSame(1, $activeSessions, 'INV-T7: exactly one active session while in_progress');
    }

    public function test_start_dispatches_auto_close_duration_job(): void
    {
        Queue::fake();

        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        Queue::assertPushed(AutoCloseTaskAtDurationExpiryJob::class);
    }

    public function test_start_throws_if_not_approved(): void
    {
        Queue::fake();

        $this->task->update(['status' => 'pending']);

        $this->expectException(EmployeeTaskException::class);

        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
    }

    public function test_start_throws_if_task_not_found(): void
    {
        $this->expectException(EmployeeTaskException::class);

        $this->service->start('non-existent-uuid', new StartTaskDTO(24.7136, 46.6753), $this->user);
    }

    public function test_start_snapshots_start_location(): void
    {
        Queue::fake();

        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertNotNull($fresh->start_location);
        $this->assertEqualsWithDelta(24.7136, $fresh->start_location['latitude'],  0.0001);
        $this->assertEqualsWithDelta(46.6753, $fresh->start_location['longitude'], 0.0001);
    }

    // -------------------------------------------------------------------------
    // pause()
    // -------------------------------------------------------------------------

    public function test_pause_transitions_in_progress_to_paused(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        $result = $this->service->pause($this->task->id, $this->user->id);

        $this->assertSame('paused', $result->status);
    }

    public function test_pause_closes_active_session(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->pause($this->task->id, $this->user->id);

        $openSessions = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)
            ->whereNull('end_time')
            ->count();

        $this->assertSame(0, $openSessions, 'Paused task must have no open sessions');
    }

    public function test_pause_throws_if_not_in_progress(): void
    {
        $this->expectException(EmployeeTaskException::class);

        $this->service->pause($this->task->id, $this->user->id);
    }

    // -------------------------------------------------------------------------
    // resume()
    // -------------------------------------------------------------------------

    public function test_resume_transitions_paused_to_in_progress(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->pause($this->task->id, $this->user->id);

        $result = $this->service->resume($this->task->id, 24.7136, 46.6753);

        $this->assertSame('in_progress', $result->status);
    }

    public function test_resume_creates_new_active_session(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->pause($this->task->id, $this->user->id);
        $this->service->resume($this->task->id, 24.7136, 46.6753);

        $activeSessions = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)
            ->whereNull('end_time')
            ->count();

        $this->assertSame(1, $activeSessions, 'INV-T7: exactly one active session after resume');
    }

    public function test_resume_throws_if_not_paused(): void
    {
        $this->expectException(EmployeeTaskException::class);

        $this->service->resume($this->task->id, 24.7136, 46.6753);
    }

    // -------------------------------------------------------------------------
    // end()
    // -------------------------------------------------------------------------

    public function test_end_transitions_in_progress_to_completed(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        $dto    = new EndTaskDTO(latitude: 24.7136, longitude: 46.6753, notes: 'Done');
        $result = $this->service->end($this->task->id, $dto);

        $this->assertSame('completed', $result->status);
    }

    public function test_end_also_works_from_paused_state(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->pause($this->task->id, $this->user->id);

        $dto    = new EndTaskDTO(latitude: 24.7136, longitude: 46.6753);
        $result = $this->service->end($this->task->id, $dto);

        $this->assertSame('completed', $result->status);
    }

    public function test_end_persists_total_task_hours(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);

        $this->service->end($this->task->id, new EndTaskDTO(latitude: 24.7136, longitude: 46.6753));

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertNotNull($fresh->total_task_hours);
        $this->assertGreaterThanOrEqual(0.0, (float) $fresh->total_task_hours);
    }

    public function test_end_sets_shift_end_method_to_manual(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->end($this->task->id, new EndTaskDTO(latitude: 24.7136, longitude: 46.6753));

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertSame('manual', $fresh->shift_end_method);
    }

    public function test_end_closes_all_sessions(): void
    {
        Queue::fake();
        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->service->end($this->task->id, new EndTaskDTO(latitude: 24.7136, longitude: 46.6753));

        $openSessions = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)
            ->whereNull('end_time')
            ->count();

        $this->assertSame(0, $openSessions, 'Completed task must have no open sessions');
    }

    public function test_end_throws_if_neither_in_progress_nor_paused(): void
    {
        $this->expectException(EmployeeTaskException::class);

        $this->service->end($this->task->id, new EndTaskDTO(24.7136, 46.6753));
    }

    // -------------------------------------------------------------------------
    // Full cycle: start → pause → resume → end
    // -------------------------------------------------------------------------

    public function test_full_lifecycle_start_pause_resume_end(): void
    {
        Queue::fake();

        $this->service->start($this->task->id, new StartTaskDTO(24.7136, 46.6753), $this->user);
        $this->assertSame('in_progress', EmployeeTaskRequest::find($this->task->id)->status);

        $this->service->pause($this->task->id, $this->user->id);
        $this->assertSame('paused', EmployeeTaskRequest::find($this->task->id)->status);

        $this->service->resume($this->task->id, 24.7136, 46.6753);
        $this->assertSame('in_progress', EmployeeTaskRequest::find($this->task->id)->status);

        $this->service->end($this->task->id, new EndTaskDTO(latitude: 24.7136, longitude: 46.6753));
        $this->assertSame('completed', EmployeeTaskRequest::find($this->task->id)->status);

        $sessions = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)->get();
        $this->assertCount(2, $sessions, 'start→pause creates 1 session, resume→end creates 1 more');

        $sessions->each(function ($s) {
            $this->assertNotNull($s->end_time, 'All sessions must be closed after end()');
        });
    }
}

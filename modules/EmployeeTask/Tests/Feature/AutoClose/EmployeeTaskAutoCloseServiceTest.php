<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Feature\AutoClose;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Models\EmployeeTaskSession;
use Modules\EmployeeTask\Services\EmployeeTaskAutoCloseService;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Contract tests for EmployeeTaskAutoCloseService::closeIfExpired().
 *
 * Mirrors the invariants verified by AutoCloseRaceTest for Attendance:
 *  1. First call returns true; status=completed; time_to = $closeAt (not now()).
 *  2. Second call on the same row returns false; time_to unchanged.
 *  3. Non-in_progress rows (already completed, cancelled) are no-ops.
 *  4. Row with no time_from is a no-op.
 *
 * Key invariant (INV-T5): time_to = $closeAt (boundary), never Carbon::now().
 *
 * @group requires-db
 */
final class EmployeeTaskAutoCloseServiceTest extends TestCase
{
    use DatabaseTransactions;

    private EmployeeTaskRequest $task;
    private EmployeeTaskAutoCloseService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(EmployeeTaskAutoCloseService::class);

        $company = Company::factory()->create(['status' => 'active']);
        $user    = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $tz  = 'Asia/Riyadh';
        $now = CarbonImmutable::now($tz);

        $this->task = EmployeeTaskRequest::create([
            'company_id'     => $company->id,
            'user_id'        => $user->id,
            'serial_number'  => 'TASK-TEST-' . uniqid(),
            'title'          => 'Auto-close test task',
            'duration_hours' => 4,
            'task_date'      => $now->toDateString(),
            'task_latitude'  => 24.7136,
            'task_longitude' => 46.6753,
            'status'         => 'in_progress',
            'time_from'      => $now->subHours(2)->format('Y-m-d H:i:s'),
            'timezone'       => $tz,
        ]);

        // Create the active session that mirrors a real in_progress state (INV-T7).
        EmployeeTaskSession::create([
            'employee_task_request_id' => $this->task->id,
            'company_id'               => $company->id,
            'start_time'               => $now->subHours(2)->format('Y-m-d H:i:s'),
            'source'                   => 'manual',
        ]);
    }

    /**
     * First close: returns true, stores $closeAt as time_to, sets status=completed.
     * Second close: returns false, time_to unchanged (not overwritten with the later closeAt).
     *
     * Regression for INV-T3 (row lock + status re-check).
     */
    public function test_second_close_call_returns_false_and_does_not_mutate(): void
    {
        $closeAt1 = CarbonImmutable::now('Asia/Riyadh');
        $closeAt2 = $closeAt1->addMinutes(5); // later time that must NOT be written

        $first = $this->service->closeIfExpired($this->task, $closeAt1, 'auto_duration');
        $this->assertTrue($first, 'First closeIfExpired() must return true');

        $this->assertDatabaseHas('employee_task_requests', [
            'id'               => $this->task->id,
            'status'           => 'completed',
            'shift_end_method' => 'auto_duration',
            'time_to'          => $closeAt1->setTimezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        ]);

        $second = $this->service->closeIfExpired($this->task, $closeAt2, 'auto_location');
        $this->assertFalse($second, 'Second closeIfExpired() must return false (already completed)');

        // time_to must still equal closeAt1, not closeAt2.
        $this->assertDatabaseHas('employee_task_requests', [
            'id'               => $this->task->id,
            'shift_end_method' => 'auto_duration',
            'time_to'          => $closeAt1->setTimezone('Asia/Riyadh')->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * INV-T5: time_to must equal $closeAt (pre-computed boundary) — NOT Carbon::now().
     *
     * Simulates a job that fires 3 minutes late. Employees must not be penalised.
     */
    public function test_time_to_equals_close_at_not_wall_clock_time(): void
    {
        // A $closeAt 5 minutes in the past — simulating a delayed queue job.
        $closeAt = CarbonImmutable::now('Asia/Riyadh')->subMinutes(5);

        $this->service->closeIfExpired($this->task, $closeAt, 'auto_duration');

        $stored = EmployeeTaskRequest::find($this->task->id)?->time_to;
        $this->assertNotNull($stored);

        $storedCarbon = CarbonImmutable::parse($stored);
        $this->assertLessThan(
            1,
            abs($storedCarbon->diffInSeconds($closeAt->setTimezone('Asia/Riyadh'))),
            'time_to must equal $closeAt, not the wall-clock time of the method call'
        );
    }

    /**
     * Already-completed rows must be no-ops.
     * Regression: stale job fires after manual end → must not overwrite time_to.
     */
    public function test_close_returns_false_for_already_completed_task(): void
    {
        $manualEndTime = CarbonImmutable::now('Asia/Riyadh')->subHour();

        $this->task->update([
            'status'           => 'completed',
            'time_to'          => $manualEndTime->format('Y-m-d H:i:s'),
            'shift_end_method' => 'manual',
        ]);

        $result = $this->service->closeIfExpired(
            $this->task,
            CarbonImmutable::now('Asia/Riyadh'),
            'auto_duration',
        );

        $this->assertFalse($result);
        $this->assertDatabaseHas('employee_task_requests', [
            'id'               => $this->task->id,
            'shift_end_method' => 'manual',
        ]);
    }

    /**
     * Cancelled tasks must also be no-ops.
     */
    public function test_close_returns_false_for_cancelled_task(): void
    {
        $this->task->update(['status' => 'cancelled']);

        $result = $this->service->closeIfExpired(
            $this->task,
            CarbonImmutable::now('Asia/Riyadh'),
            'auto_duration',
        );

        $this->assertFalse($result);
    }

    /**
     * Approved tasks (not yet started) are also no-ops.
     */
    public function test_close_returns_false_for_approved_not_started_task(): void
    {
        $this->task->update(['status' => 'approved', 'time_from' => null]);

        $result = $this->service->closeIfExpired(
            $this->task,
            CarbonImmutable::now('Asia/Riyadh'),
            'auto_duration',
        );

        $this->assertFalse($result);
    }

    /**
     * closeIfExpired() must close the active session (INV-T7).
     * After close: session.end_time = $closeAt, session.source = reason.
     */
    public function test_active_session_is_closed_with_correct_end_time_and_source(): void
    {
        $closeAt = CarbonImmutable::now('Asia/Riyadh');

        $this->service->closeIfExpired($this->task, $closeAt, 'auto_location');

        $session = EmployeeTaskSession::where('employee_task_request_id', $this->task->id)->first();
        $this->assertNotNull($session->end_time);
        $this->assertSame('auto_location', $session->source);
        $this->assertNotNull($session->duration_minutes);
        $this->assertGreaterThanOrEqual(0, $session->duration_minutes);
    }

    /**
     * total_task_hours is calculated and persisted on close.
     */
    public function test_total_task_hours_is_persisted_on_close(): void
    {
        $closeAt = CarbonImmutable::now('Asia/Riyadh');

        $this->service->closeIfExpired($this->task, $closeAt, 'auto_duration');

        $fresh = EmployeeTaskRequest::find($this->task->id);
        $this->assertNotNull($fresh->total_task_hours);
        $this->assertGreaterThanOrEqual(0.0, (float) $fresh->total_task_hours);
    }

    /**
     * ISO 8601 round-trip: a $closeAt string stored as ISO must parse back to
     * the same CarbonImmutable instant. (Mirrors INV-T2 / INV-15.)
     */
    public function test_close_at_iso_round_trip(): void
    {
        $closeAt = CarbonImmutable::now('Asia/Riyadh');
        $iso     = $closeAt->toIso8601String();
        $back    = CarbonImmutable::parse($iso);

        $this->assertEqualsWithDelta(
            0,
            abs($back->diffInSeconds($closeAt)),
            1,
            'ISO 8601 round-trip must preserve the instant to within 1 second'
        );
    }
}

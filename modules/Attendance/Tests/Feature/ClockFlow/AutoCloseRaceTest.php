<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\ClockFlow;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AutoCloseAttendanceService;
use Modules\Company\Models\Company;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Verifies that AutoCloseAttendanceService::closeIfExpired() is idempotent and
 * stores the deterministic $closeAt boundary — not the wall-clock instant.
 *
 * Regression for C1: "AutoCloseJob + AutoCloseStaleShiftsCommand run
 * concurrently → both read status=active → both write clock_out_time."
 *
 * The SELECT … FOR UPDATE row lock inside closeIfExpired() ensures the second
 * concurrent caller re-reads status=completed and returns false, leaving
 * clock_out_time unchanged. These sequential tests prove the contract; true
 * parallel concurrency cannot be exercised in a single PHP process.
 *
 * Invariants under test:
 *  1. First call returns true;  status=completed;  clock_out_time = $closeAt.
 *  2. Second call returns false; clock_out_time unchanged (not overwritten with the later time).
 *  3. Non-active rows are no-ops.
 *  4. Determinism: stored clock_out_time == $closeAt, never now() at call time.
 *
 * @group requires-db
 */
final class AutoCloseRaceTest extends TestCase
{
    use DatabaseTransactions;

    private Attendance $attendance;
    private AutoCloseAttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(AutoCloseAttendanceService::class);

        $company = Company::factory()->create(['status' => 'active']);
        $user    = User::factory()->create(['company_id' => $company->id]);

        // actingAs initialises any auth-based tenant context used by Attendance queries.
        $this->actingAs($user);

        $now = CarbonImmutable::now('Asia/Riyadh');

        $this->attendance = Attendance::create([
            'user_id'        => $user->id,
            'company_id'     => $company->id,
            'status'         => Attendance::STATUS_ACTIVE,
            'clock_in_time'  => $now->subHours(8)->format('Y-m-d H:i:s'),
            'clock_out_time' => null,
            'start_time'     => $now->subHours(8)->format('Y-m-d H:i:s'),
            'end_time'       => $now->format('Y-m-d H:i:s'),
            'timezone'       => 'Asia/Riyadh',
        ]);
    }

    /**
     * First close succeeds and stores the exact $closeAt boundary.
     * Second close on the same row returns false and leaves the row untouched.
     *
     * Simulates: AutoCloseJob fires → succeeds → AutoCloseStaleShiftsCommand
     * fires 30 s later → must be a no-op.
     */
    public function test_second_close_call_returns_false_and_does_not_mutate(): void
    {
        $closeAt1 = CarbonImmutable::now('Asia/Riyadh');
        // A later time that must NOT be written.
        $closeAt2 = $closeAt1->addMinutes(3);

        $first = $this->service->closeIfExpired($this->attendance, $closeAt1, 'auto_max_ot');
        $this->assertTrue($first, 'First closeIfExpired() must return true');

        $this->assertDatabaseHas('attendances', [
            'id'               => $this->attendance->id,
            'status'           => Attendance::STATUS_COMPLETED,
            'shift_end_method' => 'auto_max_ot',
            'clock_out_time'   => $closeAt1->format('Y-m-d H:i:s'),
        ]);

        // Simulate the second concurrent writer acquiring the lock after the first committed.
        $second = $this->service->closeIfExpired($this->attendance, $closeAt2, 'auto_next_shift');
        $this->assertFalse($second, 'Second closeIfExpired() must return false (already completed)');

        // clock_out_time must still equal closeAt1 — the second call must not overwrite it.
        $this->assertDatabaseHas('attendances', [
            'id'               => $this->attendance->id,
            'clock_out_time'   => $closeAt1->format('Y-m-d H:i:s'),
            'shift_end_method' => 'auto_max_ot',
        ]);
    }

    /**
     * Already-completed rows must be no-ops (guard condition check).
     *
     * Regression: without the status guard, a stale job that fires on a row
     * that was already manually closed would overwrite clock_out_time.
     */
    public function test_close_returns_false_for_already_completed_attendance(): void
    {
        $existingCloseTime = CarbonImmutable::now('Asia/Riyadh')->subHour();

        $this->attendance->update([
            'status'           => Attendance::STATUS_COMPLETED,
            'clock_out_time'   => $existingCloseTime->format('Y-m-d H:i:s'),
            'shift_end_method' => 'manual',
        ]);

        $result = $this->service->closeIfExpired(
            $this->attendance,
            CarbonImmutable::now(),
            'auto_max_ot',
        );

        $this->assertFalse($result, 'closeIfExpired() must return false for a completed row');

        // The manually set clock_out_time must not be overwritten.
        $this->assertDatabaseHas('attendances', [
            'id'               => $this->attendance->id,
            'shift_end_method' => 'manual',
            'clock_out_time'   => $existingCloseTime->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Deterministic clock_out_time: the stored value equals $closeAt — the
     * pre-computed boundary — NOT the wall-clock time when the job fired.
     *
     * Regression for §11.4: if the job fires 3 minutes late, employees must not
     * be penalised with extra overtime. The boundary is always end_time + max_over_time,
     * computed at clock-in time and stored as the job payload.
     */
    public function test_clock_out_time_equals_close_at_not_wall_clock_time(): void
    {
        // A $closeAt 5 minutes in the past — simulating a job that ran late.
        $closeAt = CarbonImmutable::now('Asia/Riyadh')->subMinutes(5);

        $this->service->closeIfExpired($this->attendance, $closeAt, 'auto_max_ot');

        $this->assertDatabaseHas('attendances', [
            'id'             => $this->attendance->id,
            'clock_out_time' => $closeAt->format('Y-m-d H:i:s'),
        ]);

        // Verify it is NOT the current wall-clock time.
        $stored = Attendance::find($this->attendance->id)?->clock_out_time;
        $this->assertNotNull($stored);

        $storedCarbon = CarbonImmutable::parse($stored);
        // The stored time should be within 1 second of $closeAt, not of now().
        $this->assertLessThan(
            1,
            abs($storedCarbon->diffInSeconds($closeAt)),
            'clock_out_time must equal $closeAt, not the wall-clock time of the method call',
        );
    }

    /**
     * A row with no clock_in_time (e.g. a waiting record) must be a no-op.
     */
    public function test_close_returns_false_when_clock_in_time_is_null(): void
    {
        $this->attendance->update(['clock_in_time' => null]);

        $result = $this->service->closeIfExpired(
            $this->attendance,
            CarbonImmutable::now(),
            'auto_max_ot',
        );

        $this->assertFalse($result, 'closeIfExpired() must return false when clock_in_time is null');
    }
}

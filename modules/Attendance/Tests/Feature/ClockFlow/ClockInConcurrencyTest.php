<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Feature\ClockFlow;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\Attendance\Models\Attendance;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\Models\Company;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Regression for C1: verifies the guard against duplicate active attendances.
 *
 * PHP test processes are single-threaded; true parallel concurrency cannot be
 * proven here. These tests establish the sequential invariant: a second
 * clock-in while a shift is already active is rejected (HTTP 400,
 * AttendanceException::alreadyClockedIn()), ensuring at most one active row
 * per user exists at any moment.
 *
 * The SELECT … FOR UPDATE row lock inside AttendanceService enforces this same
 * invariant across concurrent HTTP workers: the second writer re-reads the
 * locked row, finds STATUS_ACTIVE, and throws — leaving no duplicate row.
 *
 * @group requires-db
 */
final class ClockInConcurrencyTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create(['status' => 'active']);

        $branch = ManagementHierarchy::factory()->create([
            'company_id' => $company->id,
            'name'       => 'Main Branch',
            'type'       => 'branch',
            'parent_id'  => null,
        ]);

        $this->user = User::factory()->create([
            'company_id'              => $company->id,
            'management_hierarchy_id' => $branch->id,
        ]);

        $this->actingAs($this->user);
    }

    /**
     * Second clock-in while the shift is active must be rejected with HTTP 400.
     *
     * C1 invariant: exactly one active attendance row per user at any time —
     * regardless of how many concurrent clock-in requests arrive.
     */
    public function test_second_clock_in_rejected_while_shift_is_active(): void
    {
        // First clock-in must succeed.
        $this->postJson('/api/attendance/clock-in', [
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(200);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'status'  => Attendance::STATUS_ACTIVE,
        ]);

        // Second clock-in while still active must be rejected.
        $this->postJson('/api/attendance/clock-in', [
            'latitude'  => 24.7136,
            'longitude' => 46.6753,
        ])->assertStatus(400);

        // C1 guard: no duplicate row was created.
        $this->assertDatabaseCount('attendances', 1);
    }

    /**
     * Concurrent clock-in requests both succeed only if the row transitions are
     * serialised correctly: the guard must prevent more than one STATUS_ACTIVE row.
     *
     * Here we verify the count invariant from the DB perspective after two
     * sequential requests in the same HTTP session.
     */
    public function test_only_one_active_attendance_row_exists_after_duplicate_request(): void
    {
        $this->postJson('/api/attendance/clock-in', ['latitude' => 24.7136, 'longitude' => 46.6753]);
        $this->postJson('/api/attendance/clock-in', ['latitude' => 24.7136, 'longitude' => 46.6753]);

        $activeCount = Attendance::where('user_id', $this->user->id)
            ->where('status', Attendance::STATUS_ACTIVE)
            ->count();

        $this->assertLessThanOrEqual(
            1,
            $activeCount,
            'At most one active attendance row must exist per user at any time (C1 invariant).',
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Contracts\TemporaryLocationProvider;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;

/**
 * Marks an attendance row absent when the employee never clocked in before the deadline
 * (`shift_start + can_clock_in_before`, or the end of the working window when no deadline
 * is configured).
 *
 * Concurrency contract (INV-27): the row is locked with SELECT … FOR UPDATE and only
 * flipped when `clock_in_time` is still null, so a clock-in racing this writer always wins
 * and repeat runs are no-ops.
 *
 * Stateless — safe as a singleton under Octane / RoadRunner.
 */
final class AbsenceMarkingService
{
    /**
     * Flip the row to absent iff it is still un-clocked-in. Returns true when it marked.
     */
    public function markAbsentIfNoClockIn(Attendance $attendance, CarbonImmutable $absentAt): bool
    {
        return DB::transaction(function () use ($attendance, $absentAt): bool {
            $fresh = Attendance::query()->lockForUpdate()->find($attendance->id);

            // Idempotent: only a still-waiting row with no clock-in can become absent.
            if (!$fresh
                || $fresh->clock_in_time !== null
                || (bool) $fresh->is_absent
                || $fresh->status === Attendance::STATUS_ABSENT
                || $fresh->status === Attendance::STATUS_COMPLETED
                || $fresh->status === Attendance::STATUS_HOLIDAY
            ) {
                return false;
            }

            if ($this->isEngagedElsewhere($fresh, $absentAt)) {
                return false;
            }

            $noteLine = 'Auto-marked absent: no clock-in before can_clock_in_before deadline.';
            $fresh->update([
                'is_absent'  => 1,
                'status'     => Attendance::STATUS_ABSENT,
                'day_status' => 'absent',
                'absent_at'  => $absentAt->format('Y-m-d H:i:s'),
                'notes'      => trim(($fresh->notes ?? '') . "\n" . $noteLine),
            ]);

            return true;
        });
    }

    /**
     * An employee working an active task (or any future TemporaryLocationProvider) is
     * legitimately elsewhere — do not mark them absent (Feature 6 / §7.5 of the plan).
     */
    private function isEngagedElsewhere(Attendance $attendance, CarbonImmutable $at): bool
    {
        $user = User::find($attendance->user_id);
        if (!$user) {
            return false;
        }

        try {
            $providers = app()->tagged('attendance.temporary_location_providers');
        } catch (\Throwable) {
            return false;
        }

        foreach ($providers as $provider) {
            if ($provider instanceof TemporaryLocationProvider && $provider->isEngagedElsewhere($user, $at)) {
                return true;
            }

            // Tolerate duck-typed providers registered before the contract ships.
            if (!$provider instanceof TemporaryLocationProvider
                && method_exists($provider, 'isEngagedElsewhere')
                && $provider->isEngagedElsewhere($user, $at)) {
                return true;
            }
        }

        return false;
    }
}

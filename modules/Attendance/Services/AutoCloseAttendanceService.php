<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Models\Attendance;
use Illuminate\Support\Facades\Log;

/**
 * Single writer for all automatic shift-close paths.
 *
 * Design contract:
 *  - Acquires a row-level lock (SELECT … FOR UPDATE) inside a transaction.
 *  - Re-reads the row state after locking, so concurrent callers become no-ops.
 *  - Stores clock_out_time = $closeAt (the pre-computed boundary) — NOT now() —
 *    so the recorded time is always deterministic, regardless of queue delay.
 *  - Persists all calculated fields in a single UPDATE.
 *  - Stateless — safe as a singleton under Octane / RoadRunner.
 *
 * Callers: AutoClockOutAtNextShiftStartJob, SendAttendanceSilentNotificationCommand,
 *          future AutoCloseAttendanceJob.
 */
final class AutoCloseAttendanceService
{
    public function __construct(
        private readonly AttendanceCalculator $calculator,
    ) {}

    /**
     * Atomically close the shift if it is still active.
     *
     * @param  Attendance      $attendance  The row to close (used for its ID; state is re-read inside the lock).
     * @param  CarbonImmutable $closeAt     Stored as clock_out_time — the deterministic boundary time.
     * @param  string          $reason      shift_end_method value ('auto_next_shift'|'auto_max_ot'|'manual'…).
     * @return bool  true when the row was closed; false when it was already closed or not active.
     */
    public function closeIfExpired(
        Attendance $attendance,
        CarbonImmutable $closeAt,
        string $reason,
    ): bool {
        return DB::transaction(function () use ($attendance, $closeAt, $reason): bool {
            // Lock the row before acting — prevents three concurrent writers from all closing
            // the same shift (AutoClockOutAtNextShiftStartJob, command, future AutoCloseJob).
            $fresh = Attendance::query()
                ->lockForUpdate()
                ->find($attendance->id);

            if (!$fresh
                || $fresh->status !== Attendance::STATUS_ACTIVE
                || $fresh->clock_out_time !== null
                || $fresh->clock_in_time === null
            ) {
                return false;
            }

            $input  = $this->buildCalculatorInput($fresh, $closeAt);
            $result = $this->calculator->calculate($input);
            $now = Carbon::now($fresh->timezone);

            // Normalise $closeAt to the attendance's branch timezone before formatting so the
            // stored wall-clock matches the rest of the row (start_time/end_time/clock_in_time).
            $branchTz       = $fresh->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
            $closeAtInBranch = $closeAt->setTimezone($branchTz);

            $noteLine = '[Auto] Clock-out: ' . $reason . ' at ' . $closeAtInBranch->toIso8601String();
            $fresh->update([
                'clock_out_time'          => $closeAtInBranch->format('Y-m-d H:i:s'),
                'clock_out_location'      => $this->resolveLastLocation($fresh),
                'status'                  => Attendance::STATUS_COMPLETED,
                'day_status'              => 'clocked_out',
                'shift_end_method'        => $reason,
                'total_work_hours'        => $result->totalWorkHours,
                'total_break_hours'       => $result->totalBreakHours,
                'overtime_hours'          => $result->overtimeHours,
                'is_late'                 => $result->isLate,
                'late_minutes'            => $result->lateMinutes,
                'is_early_departure'      => $result->isEarlyDeparture,
                'early_departure_minutes' => $result->earlyDepartureMinutes,
                'notes'                   => trim(($fresh->notes ?? '') . "\n" . $noteLine),
            ]);

            return true;
        });
    }

    private function buildCalculatorInput(Attendance $fresh, CarbonImmutable $closeAt): CalculatorInput
    {
        $timezone = $fresh->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        // start_time/end_time/clock_in_time are stored as wall-clock strings already in the
        // branch timezone. Pass $timezone as the second argument so Carbon labels them with
        // that TZ instead of defaulting to UTC and then converting (which shifts every value
        // by the branch offset and breaks all downstream math).
        $scheduledStart = CarbonImmutable::parse($fresh->start_time, $timezone);
        $scheduledEnd   = CarbonImmutable::parse($fresh->end_time, $timezone);

        // Overnight shift: end <= start means the period crosses midnight.
        if (!$scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn = $fresh->clock_in_time
            ? CarbonImmutable::parse($fresh->clock_in_time, $timezone)
            : null;

        $totalBreakMinutes = (int) $fresh->breaks()
            ->whereNotNull('end_time')
            ->sum('duration_minutes');

        [$gracePeriodMinutes, $maxOverTimeHours] = $this->resolveConstraintParams($fresh);

        return new CalculatorInput(
            scheduledStart:    $scheduledStart,
            scheduledEnd:      $scheduledEnd,
            clockIn:           $clockIn,
            clockOut:          $closeAt->setTimezone($timezone),
            totalBreakMinutes: $totalBreakMinutes,
            gracePeriodMinutes: $gracePeriodMinutes,
            maxOverTimeHours:  $maxOverTimeHours,
            timezone:          $timezone,
        );
    }

    /**
     * @return array{0: int, 1: float}  [$gracePeriodMinutes, $maxOverTimeHours]
     */
    private function resolveConstraintParams(Attendance $attendance): array
    {
        $snapshot = $attendance->appliedAttendanceConstraint?->constraint_snapshot ?? [];

        $rules      = $snapshot['lateness_rules'] ?? [];
        $graceValue = (int) ($rules['lateness_period'] ?? 0);
        $graceUnit  = (string) ($rules['lateness_unit'] ?? 'minute');
        $grace      = $this->toMinutes($graceValue, $graceUnit);

        if ($grace <= 0) {
            $grace = (int) ($rules['grace_period_minutes'] ?? 0);
        }

        // max_over_time on the attendance row is the snapshot at clock-in time (HOURS, decimal).
        $maxOtHours = (float) ($attendance->max_over_time ?? 0.0);

        return [max(0, $grace), $maxOtHours];
    }

    private function toMinutes(int $value, string $unit): int
    {
        return match (strtolower($unit)) {
            'hour'  => $value * 60,
            'day'   => $value * 1440,
            default => $value,
        };
    }

    private function resolveLastLocation(Attendance $attendance): mixed
    {
        $points = $attendance->location_tracking ?? [];
        return !empty($points) ? end($points) : $attendance->clock_in_location;
    }
}

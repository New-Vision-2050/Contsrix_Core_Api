<?php

declare(strict_types=1);

namespace Modules\Attendance\Listeners;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Domain\Calculator\AttendanceCalculator;
use Modules\Attendance\Domain\Calculator\CalculatorInput;
use Modules\Attendance\Events\AttendanceClockedIn;
use Modules\Attendance\Models\AppliedAttendanceConstraint;
use Modules\Attendance\Models\Attendance;

class HandleAttendanceLateness
{
    public function __construct(
        private readonly AttendanceCalculator $calculator,
    ) {}

    public function handle(AttendanceClockedIn $event): void
    {
        $attendance = Attendance::with([
            'user.professionalData.attendanceConstraint',
            'breaks',
        ])->find($event->attendanceId);

        if (! $attendance) {
            Log::warning('HandleAttendanceLateness: attendance not found', ['id' => $event->attendanceId]);
            return;
        }

        $input  = $this->buildCalculatorInput($attendance);
        $result = $this->calculator->calculate($input);

        $attendance->update([
            'is_late'      => $result->isLate,
            'late_minutes' => $result->lateMinutes,
        ]);

        // Snapshot the constraint to the pivot table for audit / historical display.
        $constraint = $attendance->user?->professionalData?->attendanceConstraint;
        if ($constraint) {
            AppliedAttendanceConstraint::create([
                'attendance_id'       => $attendance->id,
                'constraint_snapshot' => $constraint->toArray(),
                'company_id'          => $attendance->company_id,
            ]);
        }
    }

    private function buildCalculatorInput(Attendance $attendance): CalculatorInput
    {
        $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';

        $scheduledStart = CarbonImmutable::parse($attendance->start_time)->setTimezone($timezone);
        $scheduledEnd   = CarbonImmutable::parse($attendance->end_time)->setTimezone($timezone);

        if (! $scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn = $attendance->clock_in_time
            ? CarbonImmutable::parse($attendance->clock_in_time)->setTimezone($timezone)
            : null;

        // Re-clock-in edge case: if this is not the user's first attendance record for the
        // same scheduled period today, anchor lateness at the earlier clock-in rather than
        // at scheduledStart. This prevents double-penalizing a user who briefly stepped out
        // and came back within the same shift period.
        if ($clockIn) {
            $previous = Attendance::where('user_id', $attendance->user_id)
                ->whereDate('clock_in_time', $clockIn->format('Y-m-d'))
                ->where('id', '!=', $attendance->id)
                ->whereNotNull('clock_in_time')
                ->orderByDesc('clock_in_time')
                ->first();

            if ($previous && $previous->clock_in_time) {
                $prevClockIn = CarbonImmutable::parse($previous->clock_in_time)->setTimezone($timezone);
                // Both clock-ins must fall within the same scheduled period.
                if ($prevClockIn->between($scheduledStart, $scheduledEnd)
                    && $clockIn->between($scheduledStart, $scheduledEnd)
                ) {
                    // Use the earlier clock-in as the effective start anchor.
                    $scheduledStart = $prevClockIn;
                }
            }
        }

        $graceMinutes = $this->resolveGraceMinutes($attendance);

        return new CalculatorInput(
            scheduledStart:     $scheduledStart,
            scheduledEnd:       $scheduledEnd,
            clockIn:            $clockIn,
            clockOut:           null,
            totalBreakMinutes:  0,
            gracePeriodMinutes: $graceMinutes,
            maxOverTimeHours:   (float) ($attendance->max_over_time ?? 0.0),
            timezone:           $timezone,
        );
    }

    private function resolveGraceMinutes(Attendance $attendance): int
    {
        $constraint = $attendance->user?->professionalData?->attendanceConstraint;
        if (! $constraint) {
            return 0;
        }

        $rules      = $constraint->constraint_config['time_rules']['lateness_rules'] ?? [];
        $graceValue = (int) ($rules['lateness_period'] ?? 0);
        $graceUnit  = (string) ($rules['lateness_unit'] ?? 'minute');

        $grace = match (strtolower($graceUnit)) {
            'hour' => $graceValue * 60,
            'day'  => $graceValue * 1440,
            default => $graceValue,
        };

        if ($grace <= 0) {
            $grace = (int) ($rules['grace_period_minutes'] ?? 0);
        }

        return max(0, $grace);
    }
}

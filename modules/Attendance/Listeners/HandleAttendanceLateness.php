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

        // Wall-clock strings stored in branch TZ; label them with that TZ instead of
        // parsing as UTC and converting (which would shift values by the branch offset).
        $scheduledStart = CarbonImmutable::parse($attendance->start_time, $timezone);
        $scheduledEnd   = CarbonImmutable::parse($attendance->end_time, $timezone);

        if (! $scheduledEnd->greaterThan($scheduledStart)) {
            $scheduledEnd = $scheduledEnd->addDay();
        }

        $clockIn = $attendance->clock_in_time
            ? CarbonImmutable::parse($attendance->clock_in_time, $timezone)
            : null;

        // Re-clock-in edge case: if this is not the user's first attendance record for the
        // same scheduled period today, anchor lateness at the earlier clock-in rather than
        // at scheduledStart. This prevents double-penalizing a user who briefly stepped out
        // and came back within the same shift period.
        //
        // Match by start_time/end_time so we only consider previous rows for the *same*
        // scheduled period — matching by date alone would pick up rows that were assigned
        // to a different period (e.g. an earlier morning shift) and produce a misleading
        // anchor.
        if ($clockIn && $attendance->start_time && $attendance->end_time) {
            $previous = Attendance::where('user_id', $attendance->user_id)
                ->where('start_time', $attendance->start_time)
                ->where('end_time', $attendance->end_time)
                ->where('id', '!=', $attendance->id)
                ->whereNotNull('clock_in_time')
                ->orderByDesc('clock_in_time')
                ->first();

            if ($previous && $previous->clock_in_time) {
                $prevClockIn = CarbonImmutable::parse($previous->clock_in_time, $timezone);
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

        $timeRules = $constraint->constraint_config['time_rules'] ?? [];

        // Lateness rules live per-day under weekly_schedule.{dayName}.lateness_rules
        // for multi-period schedules. Resolve the day from the attendance's scheduled
        // start (in branch TZ) so re-clock-in rows still pick the correct day, then
        // fall back to legacy top-level time_rules.lateness_rules for older configs.
        $rules = [];
        $weeklySchedule = $timeRules['weekly_schedule'] ?? null;
        if (is_array($weeklySchedule) && $attendance->start_time) {
            $timezone = $attendance->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
            $dayName = strtolower(CarbonImmutable::parse($attendance->start_time, $timezone)->format('l'));
            $rules = $weeklySchedule[$dayName]['lateness_rules'] ?? [];
        }

        if (empty($rules)) {
            $rules = $timeRules['lateness_rules'] ?? [];
        }

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

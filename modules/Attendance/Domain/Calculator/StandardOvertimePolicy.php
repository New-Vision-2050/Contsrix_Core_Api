<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardOvertimePolicy implements OvertimePolicy
{
    /**
     * Overtime = net worked beyond the regular target, capped by maxOverTimeHours.
     *
     * Regular target:
     *  - When {@see CalculatorInput::$maxWorkingHours} is set (> 0), the target is
     *    max_working_hours (the authoritative "total working hours"). Overtime is only
     *    counted for work performed WITHIN the shift window [scheduledStart, scheduledEnd]
     *    (no overtime outside the shift). For a re-clock-in overtime session, the regular
     *    quota already consumed by earlier rows ({@see CalculatorInput::$priorPeriodNetMinutes})
     *    is subtracted so the session's whole worked time counts as overtime.
     *  - When maxWorkingHours is unset (0), the target is the scheduled window duration and
     *    overtime is work past scheduledEnd (legacy behaviour, unchanged).
     *
     * Early clock-in time (before scheduledStart) never counts toward overtime.
     * maxOverTimeHours = 0 → no overtime allowed (cap at zero).
     *
     * @param  int $netWorkMinutes  Net worked minutes already minus breaks (from actual clock-in).
     * @return float  Overtime hours rounded to 2 decimal places.
     */
    public function calculate(CalculatorInput $input, int $netWorkMinutes): float
    {
        $hasWorkingHoursTarget = $input->maxWorkingHours > 0;

        // Clamp effective clock-in to scheduledStart so early arrival doesn't inflate overtime.
        $effectiveClockIn = $input->clockIn->greaterThan($input->scheduledStart)
            ? $input->clockIn
            : $input->scheduledStart;

        // New model: overtime cannot happen outside the shift window, so clamp the clock-out
        // to scheduledEnd. Legacy model keeps the real clock-out (overtime past end_time).
        $effectiveClockOut = $input->clockOut;
        if ($hasWorkingHoursTarget && $effectiveClockOut->greaterThan($input->scheduledEnd)) {
            $effectiveClockOut = $input->scheduledEnd;
        }

        if (!$effectiveClockOut->greaterThan($effectiveClockIn)) {
            return 0.0;
        }

        $effectiveGrossMinutes = (int) $effectiveClockIn->diffInMinutes($effectiveClockOut, false);
        $effectiveNetMinutes   = max(0, $effectiveGrossMinutes - $input->totalBreakMinutes);

        if ($hasWorkingHoursTarget) {
            $targetMinutes = (int) round($input->maxWorkingHours * 60);
            // Subtract regular hours already consumed by earlier rows in the same period.
            $regularThreshold = max(0, $targetMinutes - $input->priorPeriodNetMinutes);
        } else {
            $regularThreshold = (int) $input->scheduledStart->diffInMinutes($input->scheduledEnd);
        }

        if ($effectiveNetMinutes <= $regularThreshold) {
            return 0.0;
        }

        $overtimeMinutes = $effectiveNetMinutes - $regularThreshold;

        $capMinutes = (int) round($input->maxOverTimeHours * 60);
        $overtimeMinutes = min($overtimeMinutes, $capMinutes);

        return round($overtimeMinutes / 60, 2);
    }
}

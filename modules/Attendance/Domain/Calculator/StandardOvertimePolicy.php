<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardOvertimePolicy implements OvertimePolicy
{
    /**
     * Overtime = max(0, effectiveWorked − scheduled), then capped by maxOverTimeHours.
     * Early clock-in time (before scheduledStart) does NOT count toward overtime.
     * maxOverTimeHours = 0 or no value → no overtime allowed (cap at zero).
     *
     * @param  int $netWorkMinutes  Net worked minutes already minus breaks (from actual clock-in).
     * @return float  Overtime hours rounded to 2 decimal places.
     */
    public function calculate(CalculatorInput $input, int $netWorkMinutes): float
    {
        $scheduledMinutes = (int) $input->scheduledStart->diffInMinutes($input->scheduledEnd);

        // Clamp effective clock-in to scheduledStart so early arrival doesn't inflate overtime.
        $effectiveClockIn = $input->clockIn->greaterThan($input->scheduledStart)
            ? $input->clockIn
            : $input->scheduledStart;

        $effectiveGrossMinutes = (int) $effectiveClockIn->diffInMinutes($input->clockOut, false);
        $effectiveNetMinutes   = max(0, $effectiveGrossMinutes - $input->totalBreakMinutes);

        if ($effectiveNetMinutes <= $scheduledMinutes) {
            return 0.0;
        }

        $overtimeMinutes = $effectiveNetMinutes - $scheduledMinutes;

        $capMinutes = (int) round($input->maxOverTimeHours * 60);
        $overtimeMinutes = min($overtimeMinutes, $capMinutes);

        return round($overtimeMinutes / 60, 2);
    }
}

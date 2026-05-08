<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardOvertimePolicy implements OvertimePolicy
{
    /**
     * Overtime = max(0, worked − scheduled), then capped by maxOverTimeHours.
     * maxOverTimeHours = 0 or no value → no overtime allowed (cap at zero).
     *
     * @param  int $netWorkMinutes  Net worked minutes already minus breaks.
     * @return float  Overtime hours rounded to 2 decimal places.
     */
    public function calculate(CalculatorInput $input, int $netWorkMinutes): float
    {
        $scheduledMinutes = (int) $input->scheduledStart->diffInMinutes($input->scheduledEnd);

        if ($netWorkMinutes <= $scheduledMinutes) {
            return 0.0;
        }

        $overtimeMinutes = $netWorkMinutes - $scheduledMinutes;

        $capMinutes = (int) round($input->maxOverTimeHours * 60);
        $overtimeMinutes = min($overtimeMinutes, $capMinutes);

        return round($overtimeMinutes / 60, 2);
    }
}

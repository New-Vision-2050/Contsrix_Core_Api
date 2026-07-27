<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Rules V2 overtime: five zones, three composable flags, capped by max_over_time.
 *
 *  - `beforeEarlyClockIn` → outer pre-window zone (before scheduledStart − early window) is overtime.
 *  - `afterExtension`     → outer post-window zone (after scheduledEnd + extension) is overtime.
 *  - `afterFinishWork`    → surplus over the required hours (scheduledEnd − scheduledStart)
 *                           is overtime, on top of the zone credits.
 *  - All flags off        → identical numbers to {@see StandardOvertimePolicy} (regression guard).
 */
final class SegmentedOvertimePolicy implements OvertimePolicy
{
    /**
     * @param int $netWorkMinutes Kept for interface compatibility; zones carry the data.
     */
    public function calculate(CalculatorInput $input, int $netWorkMinutes): float
    {
        $zones   = (new ZoneSplitter())->split($input);
        $flags   = $input->overtimeFlags ?? new OvertimeFlags();
        $credited = $zones->credited();

        if ($credited <= 0 || !$input->clockIn || !$input->clockOut) {
            return 0.0;
        }

        $requiredMinutes = (int) $input->scheduledStart->diffInMinutes($input->scheduledEnd, false);

        $otMinutes = 0;

        if ($flags->beforeEarlyClockIn) {
            $otMinutes += $zones->outerPre;
        }

        if ($flags->afterExtension) {
            $otMinutes += $zones->outerPost;
        }

        if ($flags->afterFinishWork) {
            $remainingAsWork = $credited - $otMinutes;
            $otMinutes += max(0, $remainingAsWork - $requiredMinutes);
        }

        if (!$flags->beforeEarlyClockIn && !$flags->afterExtension && !$flags->afterFinishWork) {
            // Legacy behaviour: surplus over scheduled length, clock-in clamped to scheduledStart.
            $effectiveClockIn = $input->clockIn->greaterThan($input->scheduledStart)
                ? $input->clockIn
                : $input->scheduledStart;

            $effectiveNetMinutes = max(
                0,
                (int) $effectiveClockIn->diffInMinutes($input->clockOut, false) - $input->totalBreakMinutes
            );

            $otMinutes = max(0, $effectiveNetMinutes - $requiredMinutes);
        }

        $capMinutes = (int) round($input->maxOverTimeHours * 60);
        $otMinutes  = min($otMinutes, $capMinutes);
        $otMinutes  = min($otMinutes, $credited);

        return round($otMinutes / 60, 2);
    }
}

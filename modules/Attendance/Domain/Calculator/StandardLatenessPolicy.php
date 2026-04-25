<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardLatenessPolicy implements LatenessPolicy
{
    /**
     * Business rule (confirmed with stakeholder):
     *   - If clock-in <= scheduledStart + grace → not late.
     *   - If clock-in >  scheduledStart + grace → late.
     *   - late_minutes = FULL minutes past scheduledStart (not past the grace window).
     *     e.g. grace=15, user is 16 min late → late_minutes = 16 (not 1).
     *
     * @return array{0: bool, 1: int}
     */
    public function evaluate(CalculatorInput $input): array
    {
        if (!$input->clockIn) {
            return [false, 0];
        }

        $threshold = $input->scheduledStart->addMinutes($input->gracePeriodMinutes);

        if ($input->clockIn->lessThanOrEqualTo($threshold)) {
            return [false, 0];
        }

        $lateMinutes = (int) $input->scheduledStart->diffInMinutes($input->clockIn);

        return [true, $lateMinutes];
    }
}

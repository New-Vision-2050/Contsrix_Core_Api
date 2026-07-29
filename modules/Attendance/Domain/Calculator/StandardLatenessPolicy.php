<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardLatenessPolicy implements LatenessPolicy
{
    /**
     * Strict lateness (Rules V2):
     *   - clock_in <= scheduledStart → not late (early clock-in is never late).
     *   - clock_in >  scheduledStart → late, even by one minute.
     *   - late_minutes = full minutes past scheduledStart.
     *   - No grace period suppresses lateness.
     *
     * @return array{0: bool, 1: int}
     */
    public function evaluate(CalculatorInput $input): array
    {
        if (!$input->clockIn) {
            return [false, 0];
        }

        if ($input->clockIn->lessThanOrEqualTo($input->scheduledStart)) {
            return [false, 0];
        }

        $lateMinutes = (int) $input->scheduledStart->diffInMinutes($input->clockIn);

        return [true, $lateMinutes];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

final class StandardEarlyDeparturePolicy implements EarlyDeparturePolicy
{
    /**
     * @return array{0: bool, 1: int}
     */
    public function evaluate(CalculatorInput $input): array
    {
        if (!$input->clockOut || $input->clockOut->greaterThanOrEqualTo($input->scheduledEnd)) {
            return [false, 0];
        }

        $minutes = (int) $input->clockOut->diffInMinutes($input->scheduledEnd);

        return [true, $minutes];
    }
}

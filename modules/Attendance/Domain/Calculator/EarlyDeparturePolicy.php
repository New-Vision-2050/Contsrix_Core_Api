<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

interface EarlyDeparturePolicy
{
    /**
     * @return array{0: bool, 1: int}  [$isEarlyDeparture, $earlyDepartureMinutes]
     */
    public function evaluate(CalculatorInput $input): array;
}

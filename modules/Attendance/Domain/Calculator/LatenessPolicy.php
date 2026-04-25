<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

interface LatenessPolicy
{
    /**
     * @return array{0: bool, 1: int}  [$isLate, $lateMinutes]
     */
    public function evaluate(CalculatorInput $input): array;
}

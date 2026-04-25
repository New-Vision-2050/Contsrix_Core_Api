<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

interface OvertimePolicy
{
    /**
     * @param  int $netWorkMinutes  Actual worked minutes (already minus breaks).
     * @return float  Overtime hours (rounded to 2 decimal places).
     */
    public function calculate(CalculatorInput $input, int $netWorkMinutes): float;
}

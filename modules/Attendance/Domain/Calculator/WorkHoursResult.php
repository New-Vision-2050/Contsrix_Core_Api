<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Immutable result returned by AttendanceCalculator::calculate().
 * Callers persist these values into the attendance row in a single UPDATE.
 */
final class WorkHoursResult
{
    public function __construct(
        public readonly float $totalWorkHours,
        public readonly float $totalBreakHours,
        public readonly float $overtimeHours,
        public readonly bool  $isLate,
        public readonly int   $lateMinutes,
        public readonly bool  $isEarlyDeparture,
        public readonly int   $earlyDepartureMinutes,
    ) {}
}

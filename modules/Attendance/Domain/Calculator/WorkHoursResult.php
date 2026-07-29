<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

/**
 * Immutable result returned by AttendanceCalculator::calculate().
 * Callers persist these values into the attendance row in a single UPDATE.
 *
 * V2: `totalWorkHours` excludes overtime (net). Gross presence = totalWorkHours + overtimeHours.
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
        /** Net hours worked before scheduledStart (early window + outer pre-window zone). */
        public readonly float $preShiftHours = 0.0,
        /** Net hours worked between scheduledStart and scheduledEnd. */
        public readonly float $inShiftHours = 0.0,
        /** Net hours worked after scheduledEnd (extension window + outer post-window zone). */
        public readonly float $postShiftHours = 0.0,
        /** Net hours in the overtime-priced outer zones. */
        public readonly float $outsideWindowHours = 0.0,
    ) {}
}

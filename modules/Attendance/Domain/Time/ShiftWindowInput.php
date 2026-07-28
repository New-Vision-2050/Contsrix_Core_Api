<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Time;

use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;

/**
 * Immutable input for {@see ShiftWindowCalculator}. Pure value object.
 */
final readonly class ShiftWindowInput
{
    public function __construct(
        /** Scheduled period start, in branch TZ. */
        public CarbonImmutable $scheduledStart,
        /** Scheduled period end, in branch TZ (already bumped +1 day for overnight shifts). */
        public CarbonImmutable $scheduledEnd,
        /** Actual clock-in for this row; null when evaluating before clock-in. */
        public ?CarbonImmutable $clockIn,
        /** Minutes before shift start that open the ordinary-working-time window. */
        public int $earlyWindowMinutes = 0,
        /** Minutes after shift end that close the ordinary-working-time window. */
        public int $extensionMinutes = 0,
        /** First-clock-in deadline in minutes from shift start; null = no deadline. */
        public ?int $canClockInBeforeMinutes = null,
        /** Overtime cap in HOURS (decimal); also the depth of each outer overtime zone. */
        public float $maxOverTimeHours = 0.0,
        /** Completed break minutes already recorded for this row. */
        public int $completedBreakMinutes = 0,
        /** Net working minutes already credited in previous rows of the same scheduled period. */
        public int $alreadyWorkedMinutesInPeriod = 0,
        public ?OvertimeFlags $overtimeFlags = null,
        public string $timezone = 'Asia/Riyadh',
    ) {}
}

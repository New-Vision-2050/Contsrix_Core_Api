<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

use Carbon\CarbonImmutable;

/**
 * Immutable value object — all data the calculator needs, nothing it doesn't.
 * Callers build this from the persisted attendance row + applied constraint snapshot.
 */
final class CalculatorInput
{
    public function __construct(
        /** Scheduled start of the work period (in branch TZ). */
        public readonly CarbonImmutable $scheduledStart,
        /** Scheduled end of the work period (in branch TZ). */
        public readonly CarbonImmutable $scheduledEnd,
        /** Actual first clock-in time (in branch TZ). Null when not yet clocked in. */
        public readonly ?CarbonImmutable $clockIn,
        /** Actual latest clock-out time (in branch TZ). Null when still clocked in. */
        public readonly ?CarbonImmutable $clockOut,
        /** Sum of all completed break durations in minutes (pre-computed by caller). */
        public readonly int $totalBreakMinutes,
        /** Grace period before lateness is recorded, in minutes. */
        public readonly int $gracePeriodMinutes,
        /** Maximum overtime allowed, in HOURS (decimal, e.g. 4.5 = 4h30m). 0 = no overtime. */
        public readonly float $maxOverTimeHours,
        /** IANA timezone identifier used for presentation (stored on attendance row). */
        public readonly string $timezone,
    ) {}
}

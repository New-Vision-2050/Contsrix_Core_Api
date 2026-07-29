<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Calculator;

use Carbon\CarbonImmutable;

/**
 * Immutable value object — all data the calculator needs, nothing it doesn't.
 * Callers build this from the persisted attendance row + applied constraint snapshot.
 *
 * Rules V2: the grace period is gone (strict lateness — `is_late` is `clock_in > scheduledStart`).
 * Required working minutes are derived (scheduledEnd − scheduledStart), never supplied.
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
        /** Maximum overtime allowed, in HOURS (decimal, e.g. 4.5 = 4h30m). 0 = no overtime. */
        public readonly float $maxOverTimeHours,
        /** IANA timezone identifier used for presentation (stored on attendance row). */
        public readonly string $timezone,
        /** @var list<array{start: CarbonImmutable, end: CarbonImmutable}> Completed breaks, for zone attribution. */
        public readonly array $breakIntervals = [],
        /** Early-clock-in window in minutes (bounds ordinary working time before the shift). */
        public readonly int $earlyWindowMinutes = 0,
        /** Extension window in minutes (bounds ordinary working time after the shift). */
        public readonly int $extensionMinutes = 0,
        /** V2 overtime toggles snapshotted at clock-in; null = all off (legacy behaviour). */
        public readonly ?OvertimeFlags $overtimeFlags = null,
        /** When true, total_work_hours excludes overtime (V2); when false, gross (V1 rollback). */
        public readonly bool $excludeOvertimeFromWorkHours = true,
    ) {}
}

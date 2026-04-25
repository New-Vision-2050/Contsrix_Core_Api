<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Breaks;

use Carbon\CarbonImmutable;

/** Immutable value object representing a single break gap. */
final class BreakSegment
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly int             $durationMinutes,
        public readonly string          $source,
    ) {}
}

<?php

declare(strict_types=1);

namespace Modules\Attendance\Domain\Breaks;

use Carbon\CarbonImmutable;

/**
 * Pure function — no IO, no Eloquent.
 * Given the gap between a clock-out and the next clock-in, produces a BreakSegment
 * or null if the times are out-of-order or identical.
 */
final class AutoBreakComputer
{
    public function computeGap(
        CarbonImmutable $previousClockOut,
        CarbonImmutable $newClockIn,
    ): ?BreakSegment {
        if ($newClockIn->lessThanOrEqualTo($previousClockOut)) {
            return null;
        }

        return new BreakSegment(
            start: $previousClockOut,
            end: $newClockIn,
            durationMinutes: (int) $previousClockOut->diffInMinutes($newClockIn),
            source: 'auto_gap',
        );
    }
}

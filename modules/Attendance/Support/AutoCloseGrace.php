<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\CarbonImmutable;

/**
 * Auto-close wait and penalty are a fixed 2 hours, not constraint
 * `extension_minutes`. If the employee never clocks out, the job waits 2 hours
 * after expected end (or after max_over_time if that is longer), then stores
 * expected end minus 2 hours. Manual clock-out is not penalized here.
 */
final class AutoCloseGrace
{
    public const MINUTES = 120;

    /**
     * Minutes after expected clock-out before auto-close may fire.
     * Always at least 2 hours; longer when max_over_time exceeds that.
     */
    public static function delayMinutes(float $maxOverTimeHours = 0.0): int
    {
        return max((int) round($maxOverTimeHours * 60), self::MINUTES);
    }

    /**
     * Stored clock_out_time when auto-close fires: expected end minus 2 hours,
     * never before clock-in.
     */
    public static function storedClockOutAt(
        CarbonImmutable $expectedClockOutAt,
        ?CarbonImmutable $notBefore = null,
    ): CarbonImmutable {
        $stored = $expectedClockOutAt->subMinutes(self::MINUTES);
        if ($notBefore !== null && $stored->lessThan($notBefore)) {
            return $notBefore;
        }

        return $stored;
    }
}

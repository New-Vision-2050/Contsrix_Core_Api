<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\CarbonImmutable;

/**
 * Auto-close wait after expected shift end. Minutes come from the constraint
 * rules payload (`extension_minutes` / `extension_hours_shift`).
 *
 * If the employee never clocks out, the job waits that long, then stores
 * expected end minus those minutes — a penalty for not punching out.
 * Manual clock-out is a separate path and is not penalized here.
 */
final class AutoCloseGrace
{
    /**
     * Minutes after expected clock-out before auto-close may fire.
     * max_over_time is hours; extension_minutes is minutes (rules API).
     */
    public static function delayMinutes(float $maxOverTimeHours, int $extensionMinutes): int
    {
        return max((int) round($maxOverTimeHours * 60), max(0, $extensionMinutes));
    }

    /**
     * Stored clock_out_time when auto-close fires: expected end minus
     * extension_minutes, never before clock-in.
     */
    public static function storedClockOutAt(
        CarbonImmutable $expectedClockOutAt,
        int $extensionMinutes,
        ?CarbonImmutable $notBefore = null,
    ): CarbonImmutable {
        $stored = $expectedClockOutAt->subMinutes(max(0, $extensionMinutes));
        if ($notBefore !== null && $stored->lessThan($notBefore)) {
            return $notBefore;
        }

        return $stored;
    }
}

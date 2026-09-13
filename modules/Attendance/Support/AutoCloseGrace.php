<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\CarbonImmutable;

/**
 * Parked 2-hour auto-close wait + penalty. Off by default: close at shift end
 * and store that time. Flip `attendance.auto_close_grace_enabled` to restore
 * wait-2h-then-store-expected-minus-2h without rewriting callers.
 */
final class AutoCloseGrace
{
    public const MINUTES = 120;

    public static function enabledFromConfig(): bool
    {
        try {
            return (bool) config('attendance.auto_close_grace_enabled', false);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Minutes after expected clock-out before auto-close may fire.
     * Off: 0 (shift end). On: max(max_over_time, 2 hours).
     */
    public static function delayMinutes(float $maxOverTimeHours = 0.0, bool $enabled = false): int
    {
        if (! $enabled) {
            return 0;
        }

        return max((int) round($maxOverTimeHours * 60), self::MINUTES);
    }

    /**
     * Stored clock_out_time when auto-close fires.
     * Off: expected end. On: expected end minus 2 hours, never before clock-in.
     */
    public static function storedClockOutAt(
        CarbonImmutable $expectedClockOutAt,
        ?CarbonImmutable $notBefore = null,
        bool $enabled = false,
    ): CarbonImmutable {
        if (! $enabled) {
            return $expectedClockOutAt;
        }

        $stored = $expectedClockOutAt->subMinutes(self::MINUTES);
        if ($notBefore !== null && $stored->lessThan($notBefore)) {
            return $notBefore;
        }

        return $stored;
    }
}

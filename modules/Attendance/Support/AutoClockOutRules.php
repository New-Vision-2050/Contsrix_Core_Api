<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\CarbonImmutable;

/**
 * Rule-based auto clock-out (replaces the parked AutoCloseGrace).
 *
 * When enabled, a shift the employee forgot to close is auto-closed at
 * expected_clock_out + extension_minutes (the constraint's extension rule,
 * snapshotted on the row at clock-in), and the stored clock_out_time is
 * expected_clock_out minus a penalty of X% of the required shift minutes.
 *
 * Example (9h shift 11:00–20:00, extension 120, penalty 25%): the close job
 * fires at 22:00 and stores 20:00 − 2h15m = 17:45, so the day pays 6.75h.
 *
 * A manual clock-out is never penalised — it is only capped to the shift end
 * (or shift end + max_over_time when post-shift overtime is allowed). See
 * {@see ManualClockOutTime}.
 *
 * The pure methods take every input as a parameter so the domain layer
 * (ShiftWindowCalculator) stays config-free; only the *FromConfig helpers
 * read config, and only application services/commands may call them.
 */
final class AutoClockOutRules
{
    public static function enabledFromConfig(): bool
    {
        try {
            return (bool) config('attendance.rule_based_auto_clock_out_enabled', true);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function manualCapEnabledFromConfig(): bool
    {
        try {
            return (bool) config('attendance.manual_clock_out_cap_enabled', true);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function penaltyPercent(): int
    {
        try {
            return max(0, min(100, (int) config('attendance.auto_clock_out_penalty_percent', 25)));
        } catch (\Throwable) {
            return 25;
        }
    }

    /**
     * Penalty in minutes: a percent of the required shift minutes.
     * 25% of a 9h shift (540 min) = 135 min (2h15m).
     */
    public static function penaltyMinutes(int $requiredWorkMinutes, ?int $percent = null): int
    {
        $percent ??= self::penaltyPercent();

        if ($percent <= 0 || $requiredWorkMinutes <= 0) {
            return 0;
        }

        return (int) round($requiredWorkMinutes * $percent / 100);
    }

    /**
     * When the auto-close job fires: expected clock-out + the extension window.
     * Disabled: at expected clock-out (legacy behaviour).
     */
    public static function triggerAt(
        CarbonImmutable $expectedClockOutAt,
        int $extensionMinutes,
        bool $enabled,
    ): CarbonImmutable {
        if (!$enabled) {
            return $expectedClockOutAt;
        }

        return $expectedClockOutAt->addMinutes(max(0, $extensionMinutes));
    }

    /**
     * clock_out_time written by auto-close: expected clock-out minus the penalty,
     * never before the actual clock-in. Disabled: expected clock-out unchanged.
     */
    public static function storedClockOutAt(
        CarbonImmutable $expectedClockOutAt,
        ?CarbonImmutable $notBefore,
        int $requiredWorkMinutes,
        bool $enabled,
        ?int $percent = null,
    ): CarbonImmutable {
        if (!$enabled) {
            return $expectedClockOutAt;
        }

        $stored = $expectedClockOutAt->subMinutes(self::penaltyMinutes($requiredWorkMinutes, $percent));

        if ($notBefore !== null && $stored->lessThan($notBefore)) {
            return $notBefore;
        }

        return $stored;
    }
}

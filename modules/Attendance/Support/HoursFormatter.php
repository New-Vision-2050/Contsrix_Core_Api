<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

/**
 * Single source of truth for converting attendance work-hour / break / overtime / lateness
 * values into the display-facing "HH:MM" string used by report and history APIs.
 *
 * Why this class exists:
 *  - DB stores hours as DECIMAL(8,2) (see migration 2025_06_18_223500). A naive frontend that
 *    splits a decimal like "9.55" on the dot will display "09:55", which is technically wrong
 *    (0.55 hours is 33 minutes, not 55). And a decimal like "9.93" (= 9h 56m) becomes "09:93"
 *    if the FE treats the fractional digits as minutes — which is even worse because 93 is not
 *    a valid minute value at all.
 *  - The fix is to format on the BACKEND and only ever ship clean "HH:MM" strings to clients.
 *    Every report / history / summary API in this module MUST funnel through this class.
 *
 * Rules:
 *  - Input  : decimal hours (float) OR raw minutes (int).
 *  - Output : zero-padded "HH:MM" where 00 <= MM < 60. Hours are NEVER capped — a 27-hour
 *             week summary returns "27:00", not "03:00".
 *  - Negative inputs are clamped to zero (defensive — calculator already returns >= 0).
 *  - Rounding is done at the minute boundary (round to nearest minute, half-up) so cumulative
 *    sub-minute drift across many rows does not visibly accumulate.
 *
 * @see \Modules\Attendance\Tests\Unit\Support\HoursFormatterTest
 */
final class HoursFormatter
{
    /**
     * Format a decimal-hours value (e.g. 10.55 = 10h 33m) as "HH:MM".
     *
     * Examples:
     *  - 10.55 -> "10:33"
     *  - 9.93  -> "09:56"  (NOT "09:93" — 0.93h = 55.8 minutes, rounded to 56)
     *  - 0.55  -> "00:33"
     *  - 0     -> "00:00"
     *  - 27.5  -> "27:30"  (no day rollover)
     */
    public static function fromHours(float $hours): string
    {
        if ($hours <= 0.0) {
            return '00:00';
        }

        $totalMinutes = (int) round($hours * 60);

        return self::fromMinutes($totalMinutes);
    }

    /**
     * Format an integer minute count as "HH:MM".
     *
     * Examples:
     *  - 33   -> "00:33"
     *  - 93   -> "01:33"  (the carry-over case: minutes >= 60 roll into hours)
     *  - 633  -> "10:33"
     *  - 0    -> "00:00"
     */
    public static function fromMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '00:00';
        }

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Format a "decimal:2" string straight off an Eloquent attribute. Eloquent's `decimal:N`
     * cast returns a string like "10.55" (not a float) — calling fromHours() on it works
     * because PHP coerces, but this helper makes the intent explicit at call sites.
     *
     * Accepts null / empty for convenience (returns "00:00").
     */
    public static function fromDecimalString(string|float|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '00:00';
        }

        return self::fromHours((float) $value);
    }
}

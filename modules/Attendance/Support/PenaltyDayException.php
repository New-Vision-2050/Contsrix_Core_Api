<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;

/**
 * Pure rules for the penalty day-exception (استثناء اليوم) feature.
 *
 * An employee may waive a rule-based auto clock-out penalty
 * (shift_end_method = auto_extension_penalty) by spending one of their monthly
 * exceptions. No IO, no Eloquent — safe to unit-test and to call from anywhere.
 */
final class PenaltyDayException
{
    /** shift_end_method value while the penalty is still applied and decidable. */
    public const PENALTY_METHOD = 'auto_extension_penalty';

    /** shift_end_method value after the penalty was waived by an exception. */
    public const WAIVED_METHOD = 'auto_extension_penalty_waived';

    public static function monthlyLimit(): int
    {
        try {
            return max(0, (int) config('attendance.penalty_exceptions_monthly_limit', 3));
        } catch (\Throwable) {
            return 3;
        }
    }

    public static function remaining(int $limit, int $used): int
    {
        return max(0, $limit - max(0, $used));
    }

    /**
     * A row is decidable while its penalty is still applied (not yet waived) and the
     * penalized day belongs to the current calendar month — the app surfaces the
     * message the day after the close, so the window always covers "yesterday".
     *
     * @param mixed $businessDate Y-m-d string (or Carbon) of the penalized day
     */
    public static function inDecisionWindow(mixed $businessDate, Carbon $now): bool
    {
        if ($businessDate === null || $businessDate === '') {
            return false;
        }

        $day = $businessDate instanceof \DateTimeInterface
            ? Carbon::parse($businessDate)
            : Carbon::parse((string) $businessDate);

        return $day->format('Y-m') === $now->format('Y-m');
    }

    public static function isPenalized(?string $shiftEndMethod): bool
    {
        return trim((string) $shiftEndMethod) === self::PENALTY_METHOD;
    }

    public static function isWaived(?string $shiftEndMethod): bool
    {
        return trim((string) $shiftEndMethod) === self::WAIVED_METHOD;
    }
}

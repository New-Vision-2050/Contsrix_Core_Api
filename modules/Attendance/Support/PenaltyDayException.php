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
     * penalized day belongs to the current or the previous calendar month — the app
     * surfaces the message the day after the close, and "the day after" the last day
     * of a month falls in a new month, which must not close the window. A waive made
     * in the new month consumes the new month's quota.
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

        return $day->greaterThanOrEqualTo($now->copy()->startOfMonth()->subMonthNoOverflow()->startOfDay())
            && $day->lessThanOrEqualTo($now->copy()->endOfDay());
    }

    /**
     * The استثناء اليوم message is shown once the employee has clocked in today
     * (i.e. from his first clock-in onward), and only when his last worked day
     * before today still carries an undecided, in-window penalty. Before the first
     * clock-in, or once the last day is decided (or had no penalty), it stays hidden.
     *
     * Note: >= 1, not === 1 — flexible employees clock in/out several times a day
     * (one row per session), so a "first row only" rule would hide the message from
     * their second session on. The app shows it from the first clock-in until decided.
     *
     * @param int $todayClockIns attendance rows with a clock-in on today's business date
     */
    public static function shouldShowMessage(
        int $todayClockIns,
        ?string $lastShiftEndMethod,
        bool $lastAlreadyDecided,
        bool $lastInDecisionWindow,
    ): bool {
        return $todayClockIns >= 1
            && self::isPenalized($lastShiftEndMethod)
            && ! $lastAlreadyDecided
            && $lastInDecisionWindow;
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

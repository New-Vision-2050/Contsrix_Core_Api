<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\User\Models\User;

/**
 * The persistent per-employee attendance override written by
 * `PATCH /api/v1/sub_entities/records/attendance-status`.
 *
 * Stored on `users` as `manual_attendance_status` plus an inclusive
 * `manual_attendance_status_since` .. `manual_attendance_status_until` window. A null
 * `until` leaves the override open-ended; outside the window the employee falls back to
 * their attendance constraint.
 *
 * This is the single reader for that window. It exists because the same date arithmetic
 * was previously repeated in the attendance "today" rules, the history payload and the
 * sub-entity list, and a holiday granted here must read as إجازة everywhere rather than
 * عطلة (INV-18).
 */
final class ManualAttendanceStatus
{
    public const HOLIDAY = 'holiday';

    public const REQUIRED_ATTENDANCE = 'required_attendance';

    /**
     * Note stamped on the attendance rows the override materialises. Setting the override
     * rewrites every row in its date range, and shortening the range later does not undo
     * those writes, so a reader must not trust such a row once the window stops covering
     * its date — otherwise the day would stay عطلة instead of returning to the employee's
     * constraint.
     */
    public const HOLIDAY_ROW_NOTE = 'Manual sub-entity status set to holiday.';

    public const REQUIRED_ROW_NOTE = 'Manual sub-entity status set to required attendance.';

    public static function isHolidayRow(mixed $notes): bool
    {
        return is_string($notes) && trim($notes) === self::HOLIDAY_ROW_NOTE;
    }

    /**
     * The override in force on `$date`, or null when none applies.
     */
    public static function activeOn(?User $user, string $date): ?string
    {
        if (! $user) {
            return null;
        }

        return self::resolve(
            $user->manual_attendance_status ?? null,
            $user->manual_attendance_status_since ?? null,
            $user->manual_attendance_status_until ?? null,
            $date
        );
    }

    public static function isHolidayOn(?User $user, string $date): bool
    {
        return self::activeOn($user, $date) === self::HOLIDAY;
    }

    /**
     * Raw-value variant for callers holding database rows rather than models, such as the
     * reports extraction query.
     */
    public static function resolve(?string $status, mixed $since, mixed $until, string $date): ?string
    {
        if (! in_array($status, [self::HOLIDAY, self::REQUIRED_ATTENDANCE], true)) {
            return null;
        }

        $sinceDate = self::toDateString($since);
        if ($sinceDate !== null && $sinceDate > $date) {
            return null;
        }

        $untilDate = self::toDateString($until);
        if ($untilDate !== null && $date > $untilDate) {
            return null;
        }

        return $status;
    }

    public static function isHolidayFor(?string $status, mixed $since, mixed $until, string $date): bool
    {
        return self::resolve($status, $since, $until, $date) === self::HOLIDAY;
    }

    private static function toDateString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}

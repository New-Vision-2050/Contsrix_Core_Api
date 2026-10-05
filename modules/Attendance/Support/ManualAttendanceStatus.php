<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\User\Models\User;

/**
 * The persistent per-employee attendance override written by
 * `PATCH /api/v1/sub_entities/records/attendance-status`.
 *
 * Source of truth is `user_manual_attendance_overrides`: one row per granted
 * range, so disjoint days (the 27th and the 30th) can both stay إجازة. The
 * three `users.manual_attendance_status*` columns are a snapshot of the last
 * PATCH only — readers must not treat them as the full grant (INV-18).
 *
 * `fromUser()` still falls back to those columns when the table has no rows
 * yet (unmigrated, or PHPUnit fixtures).
 */
final class ManualAttendanceStatus
{
    public const HOLIDAY = 'holiday';

    public const REQUIRED_ATTENDANCE = 'required_attendance';

    /**
     * Note stamped on the attendance rows the override materialises. Setting a
     * range rewrites every row inside it, and punching the range later does not
     * undo those writes, so a reader must not trust such a row once no holiday
     * range covers its date — otherwise the day would stay عطلة instead of
     * returning to the employee's constraint.
     */
    public const HOLIDAY_ROW_NOTE = 'Manual sub-entity status set to holiday.';

    public const REQUIRED_ROW_NOTE = 'Manual sub-entity status set to required attendance.';

    public static function isHolidayRow(mixed $notes): bool
    {
        return is_string($notes) && trim($notes) === self::HOLIDAY_ROW_NOTE;
    }

    /**
     * A holiday-flagged attendance row that must not decide the calendar day.
     *
     * A row with a clock-in is kept: the employee is actually there, and a sibling
     * placeholder must not paint the day عطلة. Known leftover notes (the override,
     * or an auto-generated holiday row) are dropped once no holiday range covers the
     * date. `required_attendance` drops every remaining holiday placeholder, because
     * that instruction already outranks the holiday the row was written for (INV-21).
     */
    public static function shouldIgnoreHolidayAttendance(object $attendance, bool $requiredAttendance): bool
    {
        if (! empty($attendance->clock_in_time ?? null)) {
            return false;
        }

        $notes = $attendance->notes ?? null;
        if (self::isHolidayRow($notes) || PublicHolidayDates::isLegacyGeneratedRow($notes)) {
            return true;
        }

        if (! $requiredAttendance) {
            return false;
        }

        $flag = $attendance->is_holiday ?? null;

        return $flag === true || $flag === 1 || $flag === '1'
            || ($attendance->day_status ?? null) === 'holiday'
            || ($attendance->status ?? null) === 'holiday';
    }

    public static function overridesFor(?User $user): ManualAttendanceOverrideSet
    {
        return ManualAttendanceOverrideSet::fromUser($user);
    }

    /**
     * The override in force on `$date`, or null when none applies.
     */
    public static function activeOn(?User $user, string $date): ?string
    {
        return self::overridesFor($user)->activeOn($date);
    }

    public static function isHolidayOn(?User $user, string $date): bool
    {
        return self::activeOn($user, $date) === self::HOLIDAY;
    }

    /**
     * An admin demanding this employee attend this date. It outranks the official public
     * holiday calendar, which is country-wide and knows nothing about one person's
     * instruction, so every surface must consult this before calling a date إجازة for a
     * public holiday (INV-21).
     *
     * It does not override an approved `LeaveRequest`, nor a weekend or constraint holiday:
     * those days define no periods at all, so there is nothing to attend.
     */
    public static function isRequiredAttendanceOn(?User $user, string $date): bool
    {
        return self::activeOn($user, $date) === self::REQUIRED_ATTENDANCE;
    }

    /**
     * Raw-value variant, for callers holding database rows rather than models.
     */
    public static function isRequiredAttendanceFor(?string $status, mixed $since, mixed $until, string $date): bool
    {
        return self::resolve($status, $since, $until, $date) === self::REQUIRED_ATTENDANCE;
    }

    /**
     * Raw-value variant for a single inclusive window (legacy `users` columns,
     * or a report row that has not been joined to the override table).
     */
    public static function resolve(?string $status, mixed $since, mixed $until, string $date): ?string
    {
        return ManualAttendanceOverrideSet::fromLegacy($status, $since, $until)->activeOn($date);
    }

    public static function isHolidayFor(?string $status, mixed $since, mixed $until, string $date): bool
    {
        return self::resolve($status, $since, $until, $date) === self::HOLIDAY;
    }
}

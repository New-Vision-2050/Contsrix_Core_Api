<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Models\Attendance;

/**
 * Caps a manual clock-out punch to the rules snapshotted on the attendance row
 * (applied when attendance.manual_clock_out_cap_enabled is on). A manual punch
 * is never penalised — penalties only apply to auto clock-out (AutoClockOutRules).
 *
 * Cap rules:
 *  - Post-shift overtime NOT allowed (is_after_finish_working_hours and
 *    is_overtime_after_extension_hours_shift both off, or max_over_time = 0)
 *    → at most the shift end (expected_clock_out_time ?? end_time).
 *    e.g. a 20:30 punch on a shift ending 20:00 is stored as 20:00.
 *  - Post-shift overtime allowed → at most shift end + max_over_time.
 *    e.g. max_over_time = 20 min and a 20:30 punch is stored as 20:20.
 */
final class ManualClockOutTime
{
    public static function resolve(Attendance $attendance, mixed $requestedClockOut): Carbon
    {
        $tz = $attendance->timezone ?: date_default_timezone_get();
        $requested = self::parse($requestedClockOut, $tz);

        $shiftEnd = self::shiftEnd($attendance, $tz);
        if ($shiftEnd === null || $requested === null || $requested->lte($shiftEnd)) {
            return $requested ?? Carbon::now($tz);
        }

        $clockIn = self::parse($attendance->clock_in_time, $tz);
        if ($clockIn !== null && $shiftEnd->lt($clockIn)) {
            return $requested;
        }

        $cap = $shiftEnd->copy();
        if (self::allowsPostShiftOvertime($attendance)) {
            // max_over_time is snapshotted on the row in HOURS (decimal).
            $cap->addMinutes((int) round(((float) ($attendance->max_over_time ?? 0)) * 60));
        }

        return $requested->gt($cap) ? $cap : $requested;
    }

    public static function allowsPostShiftOvertime(Attendance $attendance): bool
    {
        $flags = OvertimeFlags::fromArray($attendance->overtime_flags);
        $maxOt = (float) ($attendance->max_over_time ?? 0);

        return ($flags->afterFinishWork || $flags->afterExtension) && $maxOt > 0;
    }

    private static function shiftEnd(Attendance $attendance, string $tz): ?Carbon
    {
        $raw = $attendance->expected_clock_out_time ?: $attendance->end_time;

        return self::parse($raw, $tz);
    }

    private static function parse(mixed $raw, string $tz): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($raw instanceof Carbon) {
            if ($raw->getTimezone()->getName() !== (new \DateTimeZone($tz))->getName()) {
                return $raw->copy()->setTimezone($tz);
            }

            return $raw->copy();
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        try {
            if (preg_match('/[zZ]|[+-]\d{2}:?\d{2}$/', $value) === 1) {
                return Carbon::parse($value)->setTimezone($tz);
            }

            return Carbon::parse($value, $tz);
        } catch (\Throwable) {
            return null;
        }
    }
}

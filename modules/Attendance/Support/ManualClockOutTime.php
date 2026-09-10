<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\Attendance\Domain\Calculator\OvertimeFlags;
use Modules\Attendance\Models\Attendance;

/**
 * Manual clock-out after shift end: if the row's snapshotted overtime flags
 * (from the role/job constraint) do not allow post-shift overtime, store the
 * shift end instead of now so the employee does not take extra hours.
 */
final class ManualClockOutTime
{
    public static function resolve(Attendance $attendance, mixed $requestedClockOut): Carbon
    {
        $tz = $attendance->timezone ?: date_default_timezone_get();
        $requested = self::parse($requestedClockOut, $tz);

        if (self::allowsPostShiftOvertime($attendance)) {
            return $requested;
        }

        $shiftEnd = self::shiftEnd($attendance, $tz);
        if ($shiftEnd === null || $requested->lte($shiftEnd)) {
            return $requested;
        }

        $clockIn = self::parse($attendance->clock_in_time, $tz);
        if ($clockIn !== null && $shiftEnd->lt($clockIn)) {
            return $requested;
        }

        return $shiftEnd;
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

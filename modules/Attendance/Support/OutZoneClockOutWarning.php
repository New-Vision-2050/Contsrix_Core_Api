<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;

/**
 * While clocked in, a GPS ping outside allowed locations asks the employee to
 * open the app and POST confirm-location. Auto clock-out is a separate flag.
 */
final class OutZoneClockOutWarning
{
    public const GRACE_MINUTES = 5;

    public const NOTIFICATION_COUNT = 3;

    public const VOICE_MESSAGE = 'تحذير أنت خارج نطاق الموقع. يرجى فتح التطبيق وتأكيد موقعك على وجه السرعة.';

    public const CONFIRM_PROMPT = 'أنت خارج نطاق الموقع. يرجى فتح التطبيق وتأكيد موقعك الآن.';

    public const CONFIRMED_INSIDE = 'تم تأكيد موقعك.';

    public const STILL_OUTSIDE = 'ما زلت خارج نطاق الموقع. يرجى تأكيد موقعك بعد العودة.';

    public const ALREADY_CLOCKED_OUT = 'تم انصرافك بسبب الخروج من الموقع.';

    public const NOT_CLOCKED_IN = 'لا يوجد دوام مفتوح.';

    /**
     * @return array{
     *     needs_location_confirm: bool,
     *     message: string,
     *     voice_message: string,
     *     attendance_id: string,
     *     warned_at: string
     * }|null
     */
    public static function payload(?Attendance $attendance): ?array
    {
        if ($attendance === null
            || empty($attendance->out_zone_warning_at)
            || ! empty($attendance->clock_out_time)
        ) {
            return null;
        }

        $now = Carbon::now($attendance->timezone ?: date_default_timezone_get());
        $warnedAt = self::warnedAt($attendance, $now);

        return [
            'needs_location_confirm' => true,
            'message' => self::CONFIRM_PROMPT,
            'voice_message' => self::VOICE_MESSAGE,
            'attendance_id' => (string) $attendance->id,
            'warned_at' => $warnedAt->toIso8601String(),
        ];
    }

    public static function warnedAt(Attendance $attendance, ?Carbon $now = null): Carbon
    {
        $raw = $attendance->out_zone_warning_at;

        if ($raw instanceof Carbon) {
            return $raw->copy();
        }

        $tz = ($now ?? Carbon::now())->getTimezone();

        return Carbon::parse((string) $raw, $tz);
    }

    public static function graceExpired(Attendance $attendance, ?Carbon $now = null): bool
    {
        if (empty($attendance->out_zone_warning_at)) {
            return false;
        }

        $tz = $attendance->timezone ?: date_default_timezone_get();
        $now ??= Carbon::now($tz);
        if ($now->getTimezone()->getName() !== (new \DateTimeZone($tz))->getName()) {
            $now = $now->copy()->setTimezone($tz);
        }

        return self::warnedAt($attendance, $now)->addMinutes(self::GRACE_MINUTES)->lte($now);
    }
}

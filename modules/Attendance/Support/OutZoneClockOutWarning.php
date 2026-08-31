<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;

/**
 * Extra 5-minute window after out_zone_minutes: voice call, then auto clock-out
 * unless the employee confirms they are back inside a work location.
 */
final class OutZoneClockOutWarning
{
    public const GRACE_MINUTES = 5;

    public const VOICE_MESSAGE = 'تحذير سيتم انصرافك آليا بسبب الخروج من الموقع. يرجى فتح التطبيق على وجه السرعة.';

    public const CONFIRM_PROMPT = 'تحذير: سيتم انصرافك بسبب الخروج من الموقع. يرجى تأكيد موقعك الآن.';

    public const CONFIRMED_INSIDE = 'تم تأكيد موقعك. لن يتم انصرافك.';

    public const STILL_OUTSIDE = 'ما زلت خارج نطاق الموقع. سيتم انصرافك تلقائياً خلال دقائق. يرجى العودة إلى الموقع.';

    public const ALREADY_CLOCKED_OUT = 'تم انصرافك بسبب الخروج من الموقع.';

    public const NOT_CLOCKED_IN = 'لا يوجد دوام مفتوح.';

    /**
     * @return array{
     *     needs_location_confirm: bool,
     *     message: string,
     *     voice_message: string,
     *     attendance_id: string,
     *     warned_at: string,
     *     clock_out_at: string,
     *     remaining_seconds: int
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
        $clockOutAt = $warnedAt->copy()->addMinutes(self::GRACE_MINUTES);
        $remaining = max(0, $clockOutAt->getTimestamp() - $now->getTimestamp());

        return [
            'needs_location_confirm' => true,
            'message' => self::CONFIRM_PROMPT,
            'voice_message' => self::VOICE_MESSAGE,
            'attendance_id' => (string) $attendance->id,
            'warned_at' => $warnedAt->toIso8601String(),
            'clock_out_at' => $clockOutAt->toIso8601String(),
            'remaining_seconds' => $remaining,
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

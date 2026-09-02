<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Modules\Attendance\Models\Attendance;

/**
 * While clocked in, mobile must keep sending GPS. If the last heartbeat
 * (clock-in location or a later tracking ping) is older than 45 minutes,
 * auto clock-out. Clock-out time is last heartbeat + 45 minutes, not cron now().
 */
final class StaleLocationClockOut
{
    public const MINUTES = 45;

    public const METHOD = 'auto_no_location';

    public static function lastHeartbeatAt(Attendance $attendance): ?Carbon
    {
        $tz = $attendance->timezone ?: date_default_timezone_get();
        $latest = self::parseTimestamp($attendance->clock_in_time, $tz);

        $tracking = $attendance->location_tracking ?? [];
        if (! is_array($tracking)) {
            return $latest;
        }

        foreach ($tracking as $point) {
            if (! is_array($point)) {
                continue;
            }

            $raw = $point['timestamp'] ?? $point['processed_at'] ?? $point['recorded_at'] ?? null;
            $at = self::parseTimestamp($raw, $tz);
            if ($at === null) {
                continue;
            }

            if ($latest === null || $at->gt($latest)) {
                $latest = $at;
            }
        }

        return $latest;
    }

    public static function closeAt(Attendance $attendance): ?CarbonImmutable
    {
        $heartbeat = self::lastHeartbeatAt($attendance);
        if ($heartbeat === null) {
            return null;
        }

        return CarbonImmutable::instance($heartbeat->copy()->addMinutes(self::MINUTES));
    }

    public static function isStale(Attendance $attendance, ?Carbon $now = null): bool
    {
        if (empty($attendance->clock_in_time) || ! empty($attendance->clock_out_time)) {
            return false;
        }

        $closeAt = self::closeAt($attendance);
        if ($closeAt === null) {
            return false;
        }

        $tz = $attendance->timezone ?: date_default_timezone_get();
        $now ??= Carbon::now($tz);
        if ($now->getTimezone()->getName() !== (new \DateTimeZone($tz))->getName()) {
            $now = $now->copy()->setTimezone($tz);
        }

        return $closeAt->lte($now);
    }

    private static function parseTimestamp(mixed $raw, string $tz): ?Carbon
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

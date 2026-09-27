<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

/**
 * Pure decision for the clock-out geofence: may this clock-out punch proceed?
 *
 * Clock-in has always hard-blocked outside the allowed locations; clock-out used
 * to succeed from anywhere and only log a violation after the fact. This class is
 * the yes/no behind the hard block — no IO, no Eloquent, safe to unit-test.
 *
 * Allowed-location lists are the same merged set the app sees in
 * user-constraint/today (location_work + additional_locations), and matching goes
 * through GeofenceMatch so a punch clock-in accepts can never be one this rejects.
 */
final class ClockOutGeofence
{
    /** Request carried no usable GPS fix. */
    public const MISSING_LOCATION = 'missing_location';

    /** Request GPS is outside every allowed location. */
    public const OUTSIDE_ALLOWED = 'outside_allowed_locations';

    /**
     * @param array<int, array<string, mixed>> $allowedLocations merged allowed geofences
     * @param array<string, mixed>|null        $requestLocation  clock-out request location
     * @param bool                             $fieldWorkExempt  employee has field work today
     *
     * @return string|null null = allow the clock-out, otherwise a self::… code
     */
    public static function violation(array $allowedLocations, ?array $requestLocation, bool $fieldWorkExempt): ?string
    {
        // No geofence configured for this employee → nothing to enforce.
        if ($allowedLocations === [] || $fieldWorkExempt) {
            return null;
        }

        $lat = $requestLocation['latitude'] ?? null;
        $lng = $requestLocation['longitude'] ?? null;

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return self::MISSING_LOCATION;
        }

        return GeofenceMatch::first((float) $lat, (float) $lng, $allowedLocations) === null
            ? self::OUTSIDE_ALLOWED
            : null;
    }
}

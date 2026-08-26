<?php

declare(strict_types=1);

namespace Modules\Attendance\Support;

/**
 * Which allowed geofence a coordinate falls inside.
 *
 * Clock-in validation only ever needed a yes/no answer, so it collapsed the match to a
 * boolean. Attributing a punch to a task site needs the winning circle itself (INV-20),
 * and both must agree on the predicate — a punch validation accepted cannot be one this
 * class rejects. Kept dependency-free so either side can use it.
 */
final class GeofenceMatch
{
    private const EARTH_RADIUS_KM = 6371;

    /**
     * The first location whose radius (metres) contains the coordinate, in the order given,
     * or null when it is outside all of them. Entries missing coordinates or a radius are
     * skipped rather than treated as matches.
     *
     * @param  array<int, array<string, mixed>>  $locations
     * @return array<string, mixed>|null
     */
    public static function first(float $latitude, float $longitude, array $locations): ?array
    {
        foreach ($locations as $location) {
            if (! isset($location['latitude'], $location['longitude'], $location['radius'])) {
                continue;
            }

            $metres = self::distanceInMetres(
                $latitude,
                $longitude,
                (float) $location['latitude'],
                (float) $location['longitude']
            );

            if ($metres <= (float) $location['radius']) {
                return $location;
            }
        }

        return null;
    }

    public static function distanceInMetres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lonDelta / 2) * sin($lonDelta / 2);

        return self::EARTH_RADIUS_KM * (2 * atan2(sqrt($a), sqrt(1 - $a))) * 1000;
    }
}

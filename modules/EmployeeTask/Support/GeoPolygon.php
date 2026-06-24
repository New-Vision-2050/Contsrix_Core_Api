<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Support;

/**
 * Point-in-polygon utilities for custom map-drawn locations.
 *
 * A polygon is an ordered list of vertices: [{lat, lng}, {lat, lng}, ...].
 * The last vertex is implicitly connected back to the first.
 */
final class GeoPolygon
{
    /**
     * Ray-casting algorithm: determine if a single GPS point lies inside
     * a closed polygon.
     *
     * @param float $lat Point latitude
     * @param float $lng Point longitude
     * @param list<array{lat: float, lng: float}> $polygon Ordered vertices
     */
    public static function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $j = $n - 1;
        for ($i = 0; $i < $n; $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }

    /**
     * Check if a point lies inside ANY of the provided polygons.
     *
     * @param float $lat Point latitude
     * @param float $lng Point longitude
     * @param list<list<array{lat: float, lng: float}>> $polygons List of polygons
     */
    public static function isPointInAnyPolygon(float $lat, float $lng, array $polygons): bool
    {
        foreach ($polygons as $polygon) {
            if (self::isPointInPolygon($lat, $lng, $polygon)) {
                return true;
            }
        }

        return false;
    }
}

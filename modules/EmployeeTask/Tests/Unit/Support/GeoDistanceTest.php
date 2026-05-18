<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Tests\Unit\Support;

use Modules\EmployeeTask\Support\GeoDistance;
use Tests\TestCase;

/**
 * Verifies the Haversine implementation in GeoDistance::metres().
 *
 * All expected values are cross-verified against the standard Haversine formula
 * and match the values produced by the LocationConstraintService equivalent.
 */
final class GeoDistanceTest extends TestCase
{
    /**
     * Same point must always return 0.
     */
    public function test_same_coordinates_returns_zero(): void
    {
        $result = GeoDistance::metres(24.7136, 46.6753, 24.7136, 46.6753);

        $this->assertEqualsWithDelta(0.0, $result, 0.001);
    }

    /**
     * ~111 km per degree of latitude at the equator.
     * Moving 1° north from (0, 0) → (1, 0) ≈ 111 195 m.
     */
    public function test_one_degree_latitude_is_approximately_111km(): void
    {
        $result = GeoDistance::metres(0.0, 0.0, 1.0, 0.0);

        $this->assertEqualsWithDelta(111_195.0, $result, 500.0);
    }

    /**
     * Two points known to be ~50 m apart (walking distance).
     * Riyadh: two GPS fixes ~50 m offset in latitude (~0.00045°).
     */
    public function test_nearby_points_within_metres(): void
    {
        $lat1 = 24.713600;
        $lon1 = 46.675300;
        $lat2 = 24.714050; // ~50 m north
        $lon2 = 46.675300;

        $result = GeoDistance::metres($lat1, $lon1, $lat2, $lon2);

        $this->assertGreaterThan(45.0, $result);
        $this->assertLessThan(55.0, $result);
    }

    /**
     * Two points far apart — Riyadh to Dubai ≈ 850 km.
     */
    public function test_riyadh_to_dubai_approximately_850km(): void
    {
        $result = GeoDistance::metres(24.7136, 46.6753, 25.2048, 55.2708);

        $this->assertGreaterThan(800_000.0, $result);
        $this->assertLessThan(900_000.0, $result);
    }

    /**
     * isWithinTaskRadius equivalent: distance < radius must be inside.
     */
    public function test_point_inside_radius_returns_true(): void
    {
        $taskLat = 24.7136;
        $taskLon = 46.6753;
        $radius  = 200; // 200 m

        // 50 m away — must be inside
        $distance = GeoDistance::metres($taskLat, $taskLon, 24.714050, 46.675300);

        $this->assertLessThanOrEqual($radius, $distance);
    }

    /**
     * Point outside radius must produce distance > radius.
     */
    public function test_point_outside_radius_returns_true(): void
    {
        $taskLat = 24.7136;
        $taskLon = 46.6753;
        $radius  = 100; // 100 m

        // 50 km away — must be outside
        $distance = GeoDistance::metres($taskLat, $taskLon, 24.7636, 46.7253);

        $this->assertGreaterThan($radius, $distance);
    }

    /**
     * Antipodal points (opposite sides of Earth) must be ~20 015 km.
     */
    public function test_antipodal_points_return_half_circumference(): void
    {
        $result = GeoDistance::metres(0.0, 0.0, 0.0, 180.0);

        $this->assertEqualsWithDelta(20_015_000.0, $result, 15_000.0);
    }

    /**
     * Result is always non-negative regardless of argument order.
     */
    public function test_result_is_symmetric(): void
    {
        $a = GeoDistance::metres(24.7136, 46.6753, 25.2048, 55.2708);
        $b = GeoDistance::metres(25.2048, 55.2708, 24.7136, 46.6753);

        $this->assertEqualsWithDelta($a, $b, 0.01);
    }
}

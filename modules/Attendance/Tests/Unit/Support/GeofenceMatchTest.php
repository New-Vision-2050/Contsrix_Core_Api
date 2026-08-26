<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\GeofenceMatch;
use PHPUnit\Framework\TestCase;

class GeofenceMatchTest extends TestCase
{
    public function test_returns_the_containing_location(): void
    {
        $match = GeofenceMatch::first(24.7136, 46.6753, [
            ['name' => 'Far', 'latitude' => 21.4858, 'longitude' => 39.1925, 'radius' => 100],
            ['name' => 'HQ', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 100],
        ]);

        $this->assertSame('HQ', $match['name']);
    }

    public function test_returns_null_when_outside_every_radius(): void
    {
        $match = GeofenceMatch::first(24.7136, 46.6753, [
            ['latitude' => 21.4858, 'longitude' => 39.1925, 'radius' => 100],
        ]);

        $this->assertNull($match);
    }

    /**
     * Order decides the winner, which is what lets a caller give constraint locations
     * priority over an overlapping task geofence.
     */
    public function test_the_first_containing_location_wins(): void
    {
        $locations = [
            ['name' => 'office', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 500],
            ['name' => 'task', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 500],
        ];

        $this->assertSame('office', GeofenceMatch::first(24.7136, 46.6753, $locations)['name']);
    }

    /**
     * A geofence saved without coordinates would otherwise swallow every punch, since
     * `null` casts to 0.0 and the equator is "inside" a large enough radius.
     */
    public function test_locations_missing_coordinates_or_radius_are_skipped(): void
    {
        $match = GeofenceMatch::first(24.7136, 46.6753, [
            ['name' => 'broken', 'latitude' => null, 'longitude' => null, 'radius' => 100],
            ['name' => 'no radius', 'latitude' => 24.7136, 'longitude' => 46.6753],
            ['name' => 'HQ', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 100],
        ]);

        $this->assertSame('HQ', $match['name']);
    }

    public function test_a_point_exactly_on_the_radius_is_inside(): void
    {
        $metres = GeofenceMatch::distanceInMetres(24.7136, 46.6753, 24.7226, 46.6753);

        $match = GeofenceMatch::first(24.7226, 46.6753, [
            ['latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => (int) ceil($metres)],
        ]);

        $this->assertNotNull($match);
        $this->assertEqualsWithDelta(1000.0, $metres, 5.0);
    }
}

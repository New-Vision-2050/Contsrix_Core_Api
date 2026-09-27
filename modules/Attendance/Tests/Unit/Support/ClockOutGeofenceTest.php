<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Support;

use Modules\Attendance\Support\ClockOutGeofence;
use PHPUnit\Framework\TestCase;

class ClockOutGeofenceTest extends TestCase
{
    private const JEDDAH_BRANCH = ['name' => 'فرع جدة النهضة', 'latitude' => 21.511595, 'longitude' => 39.19105, 'radius' => 300];

    public function test_no_configured_locations_allows_clock_out(): void
    {
        $this->assertNull(ClockOutGeofence::violation([], null, false));
        $this->assertNull(ClockOutGeofence::violation([], ['latitude' => 24.7, 'longitude' => 46.7], false));
    }

    public function test_field_work_exemption_allows_clock_out_from_anywhere(): void
    {
        $this->assertNull(ClockOutGeofence::violation([self::JEDDAH_BRANCH], null, true));
        $this->assertNull(ClockOutGeofence::violation([self::JEDDAH_BRANCH], ['latitude' => 24.7, 'longitude' => 46.7], true));
    }

    public function test_missing_gps_is_rejected_when_locations_exist(): void
    {
        $this->assertSame(
            ClockOutGeofence::MISSING_LOCATION,
            ClockOutGeofence::violation([self::JEDDAH_BRANCH], null, false)
        );
        $this->assertSame(
            ClockOutGeofence::MISSING_LOCATION,
            ClockOutGeofence::violation([self::JEDDAH_BRANCH], ['latitude' => 'x'], false)
        );
    }

    public function test_inside_any_allowed_location_is_allowed(): void
    {
        // ~50m from the branch centre.
        $this->assertNull(ClockOutGeofence::violation(
            [self::JEDDAH_BRANCH],
            ['latitude' => 21.5119, 'longitude' => 39.1913],
            false
        ));
    }

    public function test_outside_every_allowed_location_is_rejected(): void
    {
        // Riyadh — ~850km away from the Jeddah branch.
        $this->assertSame(
            ClockOutGeofence::OUTSIDE_ALLOWED,
            ClockOutGeofence::violation([self::JEDDAH_BRANCH], ['latitude' => 24.7136, 'longitude' => 46.6753], false)
        );
    }

    public function test_additional_locations_count_as_allowed(): void
    {
        $additional = ['name' => 'موقع المشروع', 'latitude' => 24.7136, 'longitude' => 46.6753, 'radius' => 200];

        $this->assertNull(ClockOutGeofence::violation(
            [self::JEDDAH_BRANCH, $additional],
            ['latitude' => 24.7136, 'longitude' => 46.6753],
            false
        ));
    }
}

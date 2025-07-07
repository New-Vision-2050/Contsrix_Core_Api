<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\DataClasses;

use Carbon\Carbon;
use InvalidArgumentException;
use Modules\Attendance\DataClasses\BranchLocation;
use Modules\Attendance\DataClasses\LocationTrackingCollection;
use Modules\Attendance\DataClasses\LocationTrackingPoint;
use Modules\Attendance\DataClasses\TemporaryLocationException;
use PHPUnit\Framework\TestCase;

class LocationTrackingDataClassesTest extends TestCase
{
    public function test_location_tracking_point_creation_and_validation(): void
    {
        // Test valid point creation
        $point = new LocationTrackingPoint(
            latitude: 24.4539,
            longitude: 54.3773,
            timestamp: Carbon::parse('2025-06-25 09:00:00'),
            accuracy: 5.0,
            deviceId: 'iPhone-12-ABC123',
            appVersion: '1.2.3',
            batteryLevel: 85,
            networkType: '4G',
            locationSource: 'GPS'
        );

        $this->assertEquals(24.4539, $point->latitude);
        $this->assertEquals(54.3773, $point->longitude);
        $this->assertEquals('iPhone-12-ABC123', $point->deviceId);
        $this->assertEquals(85, $point->batteryLevel);
        $this->assertTrue($point->hasAcceptableAccuracy(10.0));
        $this->assertFalse($point->hasLowBattery(20));
    }

    public function test_location_tracking_point_from_array(): void
    {
        $data = [
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'timestamp' => '2025-06-25 09:00:00',
            'accuracy' => 5.0,
            'device_id' => 'iPhone-12-ABC123',
            'app_version' => '1.2.3',
            'battery_level' => 85,
            'network_type' => '4G',
            'location_source' => 'GPS'
        ];

        $point = LocationTrackingPoint::fromArray($data);
        
        $this->assertEquals(24.4539, $point->latitude);
        $this->assertEquals('iPhone-12-ABC123', $point->deviceId);
        $this->assertEquals($data, $point->toArray());
    }

    public function test_location_tracking_point_validation_errors(): void
    {
        // Test invalid latitude
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Latitude must be between -90 and 90 degrees');
        
        new LocationTrackingPoint(
            latitude: 91.0,
            longitude: 54.3773,
            timestamp: Carbon::now(),
            accuracy: 5.0,
            deviceId: 'test-device',
            appVersion: '1.0.0',
            batteryLevel: 85
        );
    }

    public function test_location_tracking_point_distance_calculations(): void
    {
        $point1 = LocationTrackingPoint::fromArray([
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'timestamp' => '2025-06-25 09:00:00',
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 85
        ]);

        $point2 = LocationTrackingPoint::fromArray([
            'latitude' => 24.4600,
            'longitude' => 54.3800,
            'timestamp' => '2025-06-25 10:00:00',
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 80
        ]);

        $distance = $point1->distanceTo($point2);
        $this->assertGreaterThan(0, $distance);
        $this->assertLessThan(1, $distance); // Should be less than 1km

        // Test within radius
        $this->assertTrue($point1->isWithinRadius(24.4539, 54.3773, 100)); // Same location
        $this->assertFalse($point1->isWithinRadius(25.0, 55.0, 100)); // Far location
    }

    public function test_location_tracking_collection_creation_and_management(): void
    {
        $trackingData = [
            [
                'latitude' => 24.4539,
                'longitude' => 54.3773,
                'timestamp' => '2025-06-25 09:00:00',
                'device_id' => 'iPhone-12-ABC123',
                'accuracy' => 5.0,
                'app_version' => '1.2.3',
                'battery_level' => 85
            ],
            [
                'latitude' => 24.4600,
                'longitude' => 54.3800,
                'timestamp' => '2025-06-25 10:00:00',
                'device_id' => 'iPhone-12-ABC123',
                'accuracy' => 4.0,
                'app_version' => '1.2.3',
                'battery_level' => 80
            ]
        ];

        $collection = LocationTrackingCollection::fromArray($trackingData);
        
        $this->assertEquals(2, $collection->count());
        $this->assertFalse($collection->isEmpty());
        $this->assertEquals(60, $collection->getTimeSpanInMinutes());
        $this->assertEquals(['iPhone-12-ABC123'], $collection->getUniqueDeviceIds());
        $this->assertFalse($collection->hasMultipleDevices());
    }

    public function test_location_tracking_collection_radius_analysis(): void
    {
        $trackingData = [
            [
                'latitude' => 24.4539, // Within radius
                'longitude' => 54.3773,
                'timestamp' => '2025-06-25 09:00:00',
                'device_id' => 'test-device',
                'accuracy' => 5.0,
                'app_version' => '1.0.0',
                'battery_level' => 85
            ],
            [
                'latitude' => 24.4600, // Outside radius
                'longitude' => 54.3800,
                'timestamp' => '2025-06-25 10:00:00',
                'device_id' => 'test-device',
                'accuracy' => 5.0,
                'app_version' => '1.0.0',
                'battery_level' => 80
            ],
            [
                'latitude' => 24.4540, // Back within radius
                'longitude' => 54.3774,
                'timestamp' => '2025-06-25 11:00:00',
                'device_id' => 'test-device',
                'accuracy' => 5.0,
                'app_version' => '1.0.0',
                'battery_level' => 75
            ]
        ];

        $collection = LocationTrackingCollection::fromArray($trackingData);
        $branchLat = 24.4539;
        $branchLon = 54.3773;
        $radius = 100; // 100 meters

        $withinRadius = $collection->getPointsWithinRadius($branchLat, $branchLon, $radius);
        $outsideRadius = $collection->getPointsOutsideRadius($branchLat, $branchLon, $radius);

        $this->assertEquals(2, $withinRadius->count());
        $this->assertEquals(1, $outsideRadius->count());

        $timeOutside = $collection->calculateTimeOutsideRadius($branchLat, $branchLon, $radius);
        $this->assertGreaterThan(0, $timeOutside);
    }

    public function test_branch_location_creation_and_validation(): void
    {
        $branch = new BranchLocation(
            name: 'Main Office',
            latitude: 24.4539,
            longitude: 54.3773,
            radius: 100.0,
            address: '123 Business St',
            description: 'Main office location'
        );

        $this->assertEquals('Main Office', $branch->name);
        $this->assertEquals(100.0, $branch->radius);
        $this->assertEquals(0.1, $branch->getRadiusInKilometers());
        $this->assertTrue($branch->isActive);
    }

    public function test_branch_location_factory_methods(): void
    {
        $office = BranchLocation::createOfficeLocation('Office', 24.4539, 54.3773);
        $warehouse = BranchLocation::createWarehouseLocation('Warehouse', 24.4539, 54.3773);
        $retail = BranchLocation::createRetailLocation('Store', 24.4539, 54.3773);
        $construction = BranchLocation::createConstructionSite('Site', 24.4539, 54.3773);

        $this->assertEquals(100.0, $office->radius);
        $this->assertEquals(200.0, $warehouse->radius);
        $this->assertEquals(50.0, $retail->radius);
        $this->assertEquals(500.0, $construction->radius);
    }

    public function test_branch_location_contains_point(): void
    {
        $branch = BranchLocation::createOfficeLocation('Office', 24.4539, 54.3773);
        
        $nearPoint = LocationTrackingPoint::fromArray([
            'latitude' => 24.4540, // Very close
            'longitude' => 54.3774,
            'timestamp' => '2025-06-25 09:00:00',
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 85
        ]);

        $farPoint = LocationTrackingPoint::fromArray([
            'latitude' => 25.0000, // Far away
            'longitude' => 55.0000,
            'timestamp' => '2025-06-25 09:00:00',
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 85
        ]);

        $this->assertTrue($branch->containsPoint($nearPoint));
        $this->assertFalse($branch->containsPoint($farPoint));
    }

    public function test_temporary_location_exception_creation(): void
    {
        $clientLocation = BranchLocation::createOfficeLocation('Client Office', 24.5126, 54.3705);
        
        $exception = new TemporaryLocationException(
            type: 'client_site',
            startTime: Carbon::parse('2025-06-25 08:00:00'),
            endTime: Carbon::parse('2025-06-25 17:00:00'),
            temporaryLocation: $clientLocation,
            reason: 'Client meeting'
        );

        $this->assertEquals('client_site', $exception->type);
        $this->assertEquals(540, $exception->getDurationInMinutes()); // 9 hours
        $this->assertEquals(9.0, $exception->getDurationInHours());
    }

    public function test_temporary_location_exception_coverage(): void
    {
        $clientLocation = BranchLocation::createOfficeLocation('Client Office', 24.5126, 54.3705);
        
        $exception = TemporaryLocationException::createClientSiteException(
            startTime: Carbon::parse('2025-06-25 08:00:00'),
            endTime: Carbon::parse('2025-06-25 17:00:00'),
            clientLocation: $clientLocation,
            reason: 'Client meeting'
        );

        // Point within exception time and location
        $coveredPoint = LocationTrackingPoint::fromArray([
            'latitude' => 24.5127, // Within client office radius
            'longitude' => 54.3706,
            'timestamp' => '2025-06-25 10:00:00', // Within exception time
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 85
        ]);

        // Point outside exception time
        $uncoveredPoint = LocationTrackingPoint::fromArray([
            'latitude' => 24.5127,
            'longitude' => 54.3706,
            'timestamp' => '2025-06-25 18:00:00', // After exception ends
            'device_id' => 'test-device',
            'accuracy' => 5.0,
            'app_version' => '1.0.0',
            'battery_level' => 85
        ]);

        $this->assertTrue($exception->coversTrackingPoint($coveredPoint));
        $this->assertFalse($exception->coversTrackingPoint($uncoveredPoint));
    }

    public function test_temporary_location_exception_factory_methods(): void
    {
        $location = BranchLocation::createOfficeLocation('Test Location', 24.4539, 54.3773);
        $start = Carbon::parse('2025-06-25 08:00:00');
        $end = Carbon::parse('2025-06-25 17:00:00');

        $clientSite = TemporaryLocationException::createClientSiteException($start, $end, $location);
        $fieldWork = TemporaryLocationException::createFieldWorkException($start, $end, $location);
        $emergency = TemporaryLocationException::createEmergencyException($start, $end, $location);
        $maintenance = TemporaryLocationException::createMaintenanceException($start, $end, $location);

        $this->assertEquals('client_site', $clientSite->type);
        $this->assertEquals('field_work', $fieldWork->type);
        $this->assertEquals('emergency', $emergency->type);
        $this->assertEquals('maintenance', $maintenance->type);
    }

    public function test_integration_scenario(): void
    {
        // Create a realistic scenario with all data classes
        $mainOffice = BranchLocation::createOfficeLocation('Main Office', 24.4539, 54.3773);
        $clientOffice = BranchLocation::createOfficeLocation('Client Office', 24.5126, 54.3705);
        
        // Create temporary exception for client visit
        $exception = TemporaryLocationException::createClientSiteException(
            startTime: Carbon::parse('2025-06-25 09:00:00'),
            endTime: Carbon::parse('2025-06-25 16:00:00'),
            clientLocation: $clientOffice,
            reason: 'Important client presentation'
        );

        // Create location tracking data
        $trackingData = [
            [
                'latitude' => 24.4540, // At main office initially
                'longitude' => 54.3774,
                'timestamp' => '2025-06-25 08:30:00',
                'device_id' => 'iPhone-12-ABC123',
                'accuracy' => 4.0,
                'app_version' => '1.2.3',
                'battery_level' => 95
            ],
            [
                'latitude' => 24.5127, // At client office during exception
                'longitude' => 54.3706,
                'timestamp' => '2025-06-25 10:00:00', // Within exception time
                'device_id' => 'iPhone-12-ABC123',
                'accuracy' => 3.0,
                'app_version' => '1.2.3',
                'battery_level' => 88
            ],
            [
                'latitude' => 24.5125, // Still at client office
                'longitude' => 54.3704,
                'timestamp' => '2025-06-25 14:00:00',
                'device_id' => 'iPhone-12-ABC123',
                'accuracy' => 5.0,
                'app_version' => '1.2.3',
                'battery_level' => 75
            ]
        ];

        $collection = LocationTrackingCollection::fromArray($trackingData);

        // Test main office coverage
        $mainOfficePoints = $collection->getPointsWithinRadius(
            $mainOffice->latitude, 
            $mainOffice->longitude, 
            $mainOffice->radius
        );
        $this->assertEquals(1, $mainOfficePoints->count());

        // Test client office coverage
        $clientOfficePoints = $collection->getPointsWithinRadius(
            $clientOffice->latitude, 
            $clientOffice->longitude, 
            $clientOffice->radius
        );
        $this->assertEquals(2, $clientOfficePoints->count());

        // Test exception coverage
        $coveredPoints = 0;
        foreach ($collection as $point) {
            if ($exception->coversTrackingPoint($point)) {
                $coveredPoints++;
            }
        }
        $this->assertEquals(2, $coveredPoints); // Two points during exception time at client office

        // Test time analysis
        $this->assertEquals(330, $collection->getTimeSpanInMinutes()); // 5.5 hours
        $this->assertTrue($collection->hasTrackingGaps(60)); // Has gaps > 1 hour (4 hour gap between 10:00-14:00)
        $this->assertFalse($collection->hasTrackingGaps(300)); // No gaps > 5 hours
    }
}

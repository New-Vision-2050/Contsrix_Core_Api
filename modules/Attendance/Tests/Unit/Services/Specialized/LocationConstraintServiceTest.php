<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\LocationConstraintService;
use Modules\Attendance\Services\RadiusEnforcementService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

class LocationConstraintServiceTest extends TestCase
{
    private LocationConstraintService $service;
    private AttendanceService $attendanceService;
    private RadiusEnforcementService $radiusService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->radiusService = $this->createMock(RadiusEnforcementService::class);
        $this->service = new LocationConstraintService(
            $this->attendanceService,
            $this->radiusService
        );
    }

    /**
     * Helper method to call protected methods
     */
    private function callProtectedMethod($object, string $methodName, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Test distance calculation - same coordinates
     */
    public function test_calculate_distance_same_coordinates(): void
    {
        // Same coordinates should return 0 distance
        $point1 = ['latitude' => 34.0522, 'longitude' => -118.2437];
        $point2 = ['latitude' => 34.0522, 'longitude' => -118.2437];

        $distance = $this->callProtectedMethod($this->service, 'calculateDistance', [$point1, $point2]);

        // Distance should be 0 meters for identical coordinates
        $this->assertEquals(0, $distance);
    }

    /**
     * Test distance calculation - known distance
     */
    public function test_calculate_distance_known_points(): void
    {
        // Los Angeles to San Francisco (about 550-600km)
        $losAngeles = ['latitude' => 34.0522, 'longitude' => -118.2437];
        $sanFrancisco = ['latitude' => 37.7749, 'longitude' => -122.4194];

        $distance = $this->callProtectedMethod($this->service, 'calculateDistance', [$losAngeles, $sanFrancisco]);

        // Distance should be approximately 550-600 km (550000-600000 meters)
        $this->assertGreaterThan(550000, $distance);
        $this->assertLessThan(600000, $distance);
    }

    /**
     * Test distance calculation - within radius
     */
    public function test_calculate_distance_within_radius(): void
    {
        // Points that are 800 meters apart
        $officeLocation = ['latitude' => 34.0522, 'longitude' => -118.2437];
        $userLocation = ['latitude' => 34.0522, 'longitude' => -118.2529]; // About 800m west

        $distance = $this->callProtectedMethod($this->service, 'calculateDistance', [$officeLocation, $userLocation]);

        // Check if within 1000m radius
        $this->assertLessThan(1000, $distance);
    }

    /**
     * Test location constraint validation - within radius
     */
    public function test_validate_location_constraint_within_radius(): void
    {
        // Create mock attendance with location within allowed radius
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 34.0522, 'longitude' => -118.2437]] // User's location
        ]);

        // Create mock constraint with geofencing enabled
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_GEOFENCING],
            ['config', [
                'geofencing_enabled' => true,
                'allowed_locations' => [
                    [
                        'name' => 'Office',
                        'latitude' => 34.0522,
                        'longitude' => -118.2437,
                        'radius' => 100 // 100 meters radius
                    ]
                ]
            ]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // No violation should be detected when user is within radius
        $this->assertFalse($result);
    }

    /**
     * Test location constraint validation - outside radius
     */
    public function test_validate_location_constraint_outside_radius(): void
    {
        // Create mock attendance with location outside allowed radius
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 34.0622, 'longitude' => -118.2537]] // About 1.2 km away from office
        ]);

        // Create mock constraint with geofencing enabled
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_GEOFENCING],
            ['config', [
                'geofencing_enabled' => true,
                'allowed_locations' => [
                    [
                        'name' => 'Office',
                        'latitude' => 34.0522,
                        'longitude' => -118.2437,
                        'radius' => 1000 // 1 km radius
                    ]
                ]
            ]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::LOCATION_GEOFENCING, $result['constraint_type']);
        $this->assertStringContainsString('outside', strtolower($result['message']));
        $this->assertArrayHasKey('user_location', $result['details']);
        $this->assertArrayHasKey('nearest_allowed_location', $result['details']);
        $this->assertArrayHasKey('distance', $result['details']);
    }

    /**
     * Test IP range validation - exact IP match
     */
    public function test_ip_range_validation_exact_match(): void
    {
        // Create mock attendance with exact IP match
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.25']
        ]);

        // Create mock constraint with IP restriction
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.25']]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // No violation should be detected for exact IP match
        $this->assertFalse($result);
    }

    /**
     * Test IP range validation - CIDR notation match
     */
    public function test_ip_range_validation_cidr_match(): void
    {
        // Create mock attendance with IP in CIDR range
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.5']
        ]);

        // Create mock constraint with CIDR IP restriction
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.0/24']]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // No violation should be detected for IP within CIDR range
        $this->assertFalse($result);
    }

    /**
     * Test IP range validation - CIDR notation no match
     */
    public function test_ip_range_validation_cidr_no_match(): void
    {
        // Create mock attendance with IP outside CIDR range
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '10.0.0.1']
        ]);

        // Create mock constraint with CIDR IP restriction
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.0/24']]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::LOCATION_IP_RESTRICTION, $result['constraint_type']);
        $this->assertStringContainsString('ip address', strtolower($result['message']));
        $this->assertEquals('10.0.0.1', $result['details']['ip_address']);
        $this->assertArrayHasKey('allowed_ranges', $result['details']);
    }

    /**
     * Test branch location validation - user within branch location radius
     */
    public function test_branch_location_validation_within_radius(): void
    {
        // Create mock attendance with location at branch office
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 40.7128, 'longitude' => -74.0060]], // NYC coordinates
            ['branch_id', 'branch-1']
        ]);

        // Create mock constraint with branch location
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_BRANCH],
            ['config', []],
            ['branch_locations', [
                'branch-1' => [
                    'name' => 'NYC Office',
                    'address' => '1 World Trade Center',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'radius' => 100
                ]
            ]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // No violation should be detected for user at branch location
        $this->assertFalse($result);
    }

    /**
     * Test branch location validation - user outside branch radius
     */
    public function test_branch_location_validation_outside_radius(): void
    {
        // Create mock attendance with location away from branch office
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 40.7300, 'longitude' => -74.0200]], // ~2km from NYC office
            ['branch_id', 'branch-1']
        ]);

        // Create mock constraint with branch location
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_BRANCH],
            ['config', []],
            ['branch_locations', [
                'branch-1' => [
                    'name' => 'NYC Office',
                    'address' => '1 World Trade Center',
                    'latitude' => 40.7128,
                    'longitude' => -74.0060,
                    'radius' => 1000 // 1km radius
                ]
            ]]
        ]);

        // Call the service method directly
        $result = $this->service->validateLocationConstraint($attendance, $constraint);

        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::LOCATION_BRANCH, $result['constraint_type']);
        $this->assertStringContainsString('branch location', strtolower($result['message']));
        $this->assertArrayHasKey('user_location', $result['details']);
        $this->assertArrayHasKey('branch_location', $result['details']);
        $this->assertArrayHasKey('distance', $result['details']);
    }
}

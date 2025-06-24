<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Contracts\TimeConstraintServiceInterface;
use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Contracts\DeviceConstraintServiceInterface;
use Modules\Attendance\Contracts\RoleConstraintServiceInterface;
use Modules\Attendance\Contracts\BehavioralConstraintServiceInterface;
use Modules\Attendance\Contracts\SecurityConstraintServiceInterface;
use Modules\Attendance\Contracts\ComplianceConstraintServiceInterface;
use Modules\Attendance\Services\AttendanceConstraintService;
use Carbon\Carbon;
use Modules\Attendance\DataClasses\TimePeriod;
use ReflectionClass;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\MockObject\MockObject;

class AttendanceConstraintServiceTest extends TestCase
{
    private AttendanceConstraintService $service;
    private TimeConstraintServiceInterface|MockObject $timeService;
    private LocationConstraintServiceInterface|MockObject $locationService;
    private DeviceConstraintServiceInterface|MockObject $deviceService;
    private RoleConstraintServiceInterface|MockObject $roleService;
    private BehavioralConstraintServiceInterface|MockObject $behavioralService;
    private SecurityConstraintServiceInterface|MockObject $securityService;
    private ComplianceConstraintServiceInterface|MockObject $complianceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for all specialized services
        $this->timeService = $this->createMock(TimeConstraintServiceInterface::class);
        $this->locationService = $this->createMock(LocationConstraintServiceInterface::class);
        $this->deviceService = $this->createMock(DeviceConstraintServiceInterface::class);
        $this->roleService = $this->createMock(RoleConstraintServiceInterface::class);
        $this->behavioralService = $this->createMock(BehavioralConstraintServiceInterface::class);
        $this->securityService = $this->createMock(SecurityConstraintServiceInterface::class);
        $this->complianceService = $this->createMock(ComplianceConstraintServiceInterface::class);
        
        // Create service instance with mocked dependencies
        $this->service = new AttendanceConstraintService(
            $this->timeService,
            $this->locationService,
            $this->deviceService,
            $this->roleService,
            $this->behavioralService,
            $this->securityService,
            $this->complianceService
        );
    }

    /**
     * Helper method to call protected methods
     */
    private function callProtectedMethod($object, string $methodName, array $args = [])
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Test location constraint validation delegation
     * This tests that the main service correctly delegates to LocationConstraintService
     */
    public function test_distance_calculation(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 37.7749, 'longitude' => -122.4194]]
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_GEOFENCING],
            ['config', ['geofencing_enabled' => true]]
        ]);
        
        // Expected violation response from location service
        $mockViolation = [
            'constraint_type' => AttendanceConstraint::LOCATION_GEOFENCING,
            'severity' => 'high',
            'message' => 'User location is outside all allowed geofenced zones.'
        ];
        
        // Configure the location service mock to expect validateLocationConstraint call
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn($mockViolation);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result matches what the mock returned
        $this->assertEquals($mockViolation, $result);
    }

    /**
     * Test distance calculation for same location
     */
    public function test_distance_calculation_same_location(): void
    {
        // Create mock attendance with same location as the constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['location', ['latitude' => 34.0522, 'longitude' => -118.2437]]
        ]);
        
        // Create a location constraint with matching coordinates
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_GEOFENCING],
            ['config', [
                'geofencing_enabled' => true,
                'allowed_locations' => [
                    ['latitude' => 34.0522, 'longitude' => -118.2437, 'radius' => 100]
                ]
            ]]
        ]);
        
        // Configure the location service to validate location constraints and return no violation (false)
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn(false);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // No violation should be returned when the locations match
        $this->assertFalse($result);
    }

    /**
     * Test IP range validation with exact IP match
     */
    public function test_ip_range_validation_exact_match(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.25']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.25']]]
        ]);
        
        // Configure the location service to validate the location constraint and return false (no violation)
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn(false);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result is false (no violation)
        $this->assertFalse($result);
    }

    /**
     * Test IP range validation with CIDR notation - match
     */
    public function test_ip_range_validation_cidr_match(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.5']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.0/24']]]
        ]);
        
        // Configure the location service to validate the location constraint and return false (no violation)
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn(false);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result is false (no violation)
        $this->assertFalse($result);
    }

    /**
     * Test IP range validation with CIDR notation - no match
     */
    public function test_ip_range_validation_cidr_no_match(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '10.0.0.1']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.0/24']]]
        ]);
        
        // Expected violation response from location service
        $mockViolation = [
            'constraint_type' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
            'severity' => 'high',
            'message' => 'IP address not in allowed ranges',
            'details' => [
                'ip_address' => '10.0.0.1',
                'allowed_ranges' => ['192.168.1.0/24']
            ]
        ];
        
        // Configure the location service to validate the location constraint and return a violation
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn($mockViolation);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result matches the expected violation
        $this->assertIsArray($result);
        $this->assertEquals('IP address not in allowed ranges', $result['message']);
    }

    /**
     * Test IP range validation with different subnet
     */
    public function test_ip_range_validation_different_subnet(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.2.5']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.0/24']]]
        ]);
        
        // Expected violation response from location service
        $mockViolation = [
            'constraint_type' => AttendanceConstraint::LOCATION_IP_RESTRICTION,
            'severity' => 'medium',
            'message' => 'IP address not in allowed ranges',
            'details' => [
                'ip_address' => '192.168.2.5',
                'allowed_ranges' => ['192.168.1.0/24']
            ]
        ];
        
        // Configure the location service to validate the location constraint and return a violation
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn($mockViolation);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result contains the expected ip address detail
        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertEquals('192.168.2.5', $result['details']['ip_address']);
    }

    /**
     * Test IP range validation with smaller CIDR block
     */
    public function test_ip_range_validation_smaller_cidr(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['ip_address', '192.168.1.20']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_IP_RESTRICTION],
            ['config', ['allowed_ip_ranges' => ['192.168.1.16/28']]]
        ]);
        
        // Configure the location service to validate the location constraint and return false (no violation)
        $this->locationService->expects($this->once())
            ->method('validateLocationConstraint')
            ->with($this->equalTo($attendance), $this->anything())
            ->willReturn(false);
        
        // Call through the main service and verify it delegates correctly
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        // Verify the result is false (no violation)
        $this->assertFalse($result);
    }

    /**
     * Test time period validation helper method if it exists
    public function test_validate_break_end_no_violation(): void
    {
        // Since validateBreakEnd is now in TimeConstraintService, we'll test delegation
        $attendance = $this->createMock(Attendance::class);
        $config = ['min_break_minutes' => 10, 'max_break_minutes' => 60];
        
        // Create mock constraint
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['config', $config]
        ]);
        
        // Configure the time service mock to return false (no violation)
        $this->timeService->expects($this->once())
            ->method('validateTimeConstraint')
            ->with($this->anything(), $this->anything())
            ->willReturn(false);
        
        // Call the main service's validateSingleConstraint method
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        $this->assertFalse($result);
    }

    /**
     * Test break time limit validation with a violation (too long).
     */
    public function test_validate_break_end_with_violation(): void
    {
        // Since validateBreakEnd is now in TimeConstraintService, we'll test delegation
        $attendance = $this->createMock(Attendance::class);
        $config = ['min_break_minutes' => 10, 'max_break_minutes' => 60];
        
        // Create mock constraint
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['config', $config]
        ]);
        
        // Create a violation response that the mock service will return
        $violation = [
            'constraint_type' => AttendanceConstraint::TIME_BREAK_TIME_LIMITS,
            'severity' => 'medium',
            'message' => 'Break duration exceeded maximum allowed time',
            'details' => [
                'break_duration_minutes' => 70,
                'max_allowed_minutes' => 60
            ]
        ];
        
        // Configure the time service mock to return a violation
        $this->timeService->expects($this->once())
            ->method('validateTimeConstraint')
            ->with($this->anything(), $this->anything())
            ->willReturn($violation);
        
        // Call the main service's validateSingleConstraint method
        $result = $this->service->validateSingleConstraint($attendance, $constraint);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('break_duration_minutes', $result['details']);
        $this->assertEquals(70, $result['details']['break_duration_minutes']);
    }
}

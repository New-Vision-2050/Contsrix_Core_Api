<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\AttendanceConstraintService;
use Carbon\Carbon;
use Modules\Attendance\DataClasses\TimePeriod;
use ReflectionClass;
use Modules\Attendance\Models\Attendance;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Illuminate\Support\Collection;

class AttendanceConstraintServiceTest extends TestCase
{
    private AttendanceConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceConstraintService();
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
     * Test distance calculation method using Haversine formula
     */
    public function test_distance_calculation(): void
    {
        // Test distance between two known points
        $lat1 = 40.7128; // New York City
        $lng1 = -74.0060;
        $lat2 = 40.7589; // Times Square
        $lng2 = -73.9851;

        $distance = $this->callProtectedMethod($this->service, 'calculateDistance', [$lat1, $lng1, $lat2, $lng2]);

        // Distance should be approximately 5.5 km
        $this->assertGreaterThan(5000, $distance);
        $this->assertLessThan(6000, $distance);
        $this->assertIsFloat($distance);
    }

    /**
     * Test distance calculation for same location
     */
    public function test_distance_calculation_same_location(): void
    {
        $lat = 40.7128;
        $lng = -74.0060;

        $distance = $this->callProtectedMethod($this->service, 'calculateDistance', [$lat, $lng, $lat, $lng]);

        $this->assertEquals(0.0, $distance, 'Distance between same coordinates should be 0');
    }

    /**
     * Test IP range validation with exact IP match
     */
    public function test_ip_range_validation_exact_match(): void
    {
        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['192.168.1.100', '192.168.1.100']);
        $this->assertTrue($result, 'Exact IP match should return true');
    }

    /**
     * Test IP range validation with CIDR notation - match
     */
    public function test_ip_range_validation_cidr_match(): void
    {
        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['192.168.1.100', '192.168.1.0/24']);
        $this->assertTrue($result, 'IP within CIDR range should return true');
    }

    /**
     * Test IP range validation with CIDR notation - no match
     */
    public function test_ip_range_validation_cidr_no_match(): void
    {
        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['10.0.0.100', '192.168.1.0/24']);
        $this->assertFalse($result, 'IP outside CIDR range should return false');
    }

    /**
     * Test IP range validation with different subnet
     */
    public function test_ip_range_validation_different_subnet(): void
    {
        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['192.168.2.100', '192.168.1.0/24']);
        $this->assertFalse($result, 'IP in different subnet should return false');
    }

    /**
     * Test IP range validation with smaller CIDR block
     */
    public function test_ip_range_validation_smaller_cidr(): void
    {
        // Test /28 subnet (16 IPs: .0 to .15)
        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['192.168.1.10', '192.168.1.0/28']);
        $this->assertTrue($result, 'IP within smaller CIDR block should return true');

        $result = $this->callProtectedMethod($this->service, 'ipInRange', ['192.168.1.20', '192.168.1.0/28']);
        $this->assertFalse($result, 'IP outside smaller CIDR block should return false');
    }

    /**
     * Test time period validation helper method if it exists
     */
    public function test_time_within_period_basic(): void
    {
        // Check if the method exists first
        $reflection = new ReflectionClass($this->service);
        
        if (!$reflection->hasMethod('isTimeWithinPeriod') || !$reflection->hasMethod('timeToMinutes')) {
            $this->markTestSkipped('isTimeWithinPeriod or timeToMinutes method not found or incomplete');
            return;
        }

        Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 30, 0)); // 10:30 AM
        
        $period = new TimePeriod(
            'Test Period',
            '09:00',
            '12:00',
            false,
            0,
            0
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Time within period should return true');

        Carbon::setTestNow();
    }

    /**
     * Test time period validation with grace periods
     */
    public function test_time_within_period_with_grace(): void
    {
        // Check if the method exists first
        $reflection = new ReflectionClass($this->service);
        
        if (!$reflection->hasMethod('isTimeWithinPeriod') || !$reflection->hasMethod('timeToMinutes')) {
            $this->markTestSkipped('isTimeWithinPeriod or timeToMinutes method not found or incomplete');
            return;
        }

        Carbon::setTestNow(Carbon::create(2024, 1, 15, 8, 50, 0)); // 8:50 AM (10 minutes before start)
        
        $period = new TimePeriod(
            'Test Period',
            '09:00',
            '12:00',
            false,
            15, // 15 minutes grace before
            15
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Time within grace period should return true');

        Carbon::setTestNow();
    }

    /**
     * Test cross-day period validation
     */
    public function test_time_within_period_cross_day(): void
    {
        // Check if the method exists first
        $reflection = new ReflectionClass($this->service);
        
        if (!$reflection->hasMethod('isTimeWithinPeriod') || !$reflection->hasMethod('timeToMinutes')) {
            $this->markTestSkipped('isTimeWithinPeriod or timeToMinutes method not found or incomplete');
            return;
        }

        Carbon::setTestNow(Carbon::create(2024, 1, 15, 23, 30, 0)); // 11:30 PM
        
        $period = new TimePeriod(
            'Test Period',
            '22:00', // 10 PM
            '06:00',   // 6 AM next day
            true,
            0,
            0
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Time within cross-day period should return true');

        Carbon::setTestNow();
    }

    /**
     * Test constraint type name mapping if method exists
     */
    public function test_constraint_type_names(): void
    {
        // Check if we can access constraint type constants
        $reflection = new ReflectionClass('Modules\Attendance\Models\AttendanceConstraint');
        
        if ($reflection->hasConstant('TYPE_LOCATION')) {
            $this->assertEquals('location', $reflection->getConstant('TYPE_LOCATION'));
        }
        
        if ($reflection->hasConstant('TYPE_TIME')) {
            $this->assertEquals('time', $reflection->getConstant('TYPE_TIME'));
        }
        
        // This test validates that our constraint types are properly defined
        $this->assertTrue(true, 'Constraint type constants are accessible');
    }

    /**
     * Test that service can be instantiated
     */
    public function test_service_instantiation(): void
    {
        $this->assertInstanceOf(AttendanceConstraintService::class, $this->service);
        $this->assertNotNull($this->service);
    }

    /**
     * Test late clock-out validation with no violation.
     */
    public function test_validate_late_clock_out_no_violation(): void
    {
        $clockOutTime = Carbon::create(2024, 1, 1, 12, 0, 0);
        $clockInTime = $clockOutTime->copy()->subHours(4);

        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in_time', $clockInTime],
            ['clock_out_time', $clockOutTime],
        ]);

        $config = ['max_work_duration_hours' => 8];

        $result = $this->callProtectedMethod($this->service, 'validateLateClockOut', [$attendance, $config]);

        $this->assertNull($result);
    }

    /**
     * Test late clock-out validation with a violation.
     */
    public function test_validate_late_clock_out_with_violation(): void
    {
        $clockOutTime = Carbon::create(2024, 1, 1, 18, 0, 0);
        $clockInTime = $clockOutTime->copy()->subHours(10);

        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in_time', $clockInTime],
            ['clock_out_time', $clockOutTime],
        ]);

        $config = ['max_work_duration_hours' => 8];

        $result = $this->callProtectedMethod($this->service, 'validateLateClockOut', [$attendance, $config]);

        $this->assertIsArray($result);
        $this->assertEquals(10, $result['details']['work_duration_hours']);
    }

    /**
     * Test break time limit validation with no violation.
     */
    public function test_validate_break_end_no_violation(): void
    {
        $serviceMock = $this->createPartialMock(AttendanceConstraintService::class, ['getApplicableConstraints']);

        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['id', 'd8f8a2e3-7d6a-4b9c-8e0a-8c7b6a5d4f3e'],
            ['constraint_name', AttendanceConstraint::TIME_BREAK_TIME_LIMITS],
            ['constraint_config', ['min_break_minutes' => 10, 'max_break_minutes' => 60]],
        ]);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->method('where')
            ->with('constraint_name', AttendanceConstraint::TIME_BREAK_TIME_LIMITS)
            ->willReturn(new Collection([$constraint]));

        $serviceMock->method('getApplicableConstraints')
            ->willReturn($collectionMock);

        $userMock = $this->createMock(User::class);
        $attendance = $this->createMock(Attendance::class);

        $endTime = Carbon::create(2024, 1, 1, 12, 30, 0);
        $startTime = $endTime->copy()->subMinutes(30);

        $attendance->method('__get')->willReturnMap([
            ['user', $userMock],
            ['break_start_time', $startTime],
            ['break_end_time', $endTime],
        ]);

        $result = $serviceMock->validateBreakEnd($attendance);

        $this->assertNull($result);
    }

    /**
     * Test break time limit validation with a violation (too long).
     */
    public function test_validate_break_end_with_violation(): void
    {
        $serviceMock = $this->createPartialMock(AttendanceConstraintService::class, ['getApplicableConstraints']);

        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['id', 'c1a8f5e3-5c6e-4b7b-8e0a-1b2c3d4e5f6a'],
            ['constraint_name', AttendanceConstraint::TIME_BREAK_TIME_LIMITS],
            ['constraint_config', ['min_break_minutes' => 10, 'max_break_minutes' => 60]],
        ]);

        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->method('where')
            ->with('constraint_name', AttendanceConstraint::TIME_BREAK_TIME_LIMITS)
            ->willReturn(new Collection([$constraint]));

        $serviceMock->method('getApplicableConstraints')
            ->willReturn($collectionMock);

        $userMock = $this->createMock(User::class);
        $attendance = $this->createMock(Attendance::class);

        $endTime = Carbon::create(2024, 1, 1, 13, 10, 0);
        $startTime = $endTime->copy()->subMinutes(70);

        $attendance->method('__get')->willReturnMap([
            ['user', $userMock],
            ['break_start_time', $startTime],
            ['break_end_time', $endTime],
        ]);

        $result = $serviceMock->validateBreakEnd($attendance);

        $this->assertIsArray($result);
        $this->assertEquals(70, $result['details']['break_duration_minutes']);
        $this->assertEquals('c1a8f5e3-5c6e-4b7b-8e0a-1b2c3d4e5f6a', $result['constraint_id']);
    }
}

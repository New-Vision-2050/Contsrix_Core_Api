<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\TimeConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Carbon\Carbon;
use Modules\Attendance\DataClasses\TimePeriod;

class TimeConstraintServiceTest extends TestCase
{
    private TimeConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TimeConstraintService();
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
     * Test time within period helper method
     */
    public function test_time_within_period_basic(): void
    {
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
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 8, 50, 0)); // 8:50 AM (10 minutes before start)
        
        $period = new TimePeriod(
            'Test Period',
            '09:00',
            '12:00',
            false,
            15, // 15 minutes grace before
            15  // 15 minutes grace after
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Time within grace period should return true');

        Carbon::setTestNow();
    }

    /**
     * Test time outside of period and grace period
     */
    public function test_time_outside_period_with_grace(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 8, 40, 0)); // 8:40 AM (20 minutes before start)
        
        $period = new TimePeriod(
            'Test Period',
            '09:00',
            '12:00',
            false,
            15, // 15 minutes grace before
            15  // 15 minutes grace after
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertFalse($result, 'Time outside period and grace should return false');

        Carbon::setTestNow();
    }

    /**
     * Test cross-day period validation
     */
    public function test_time_within_period_cross_day(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 23, 30, 0)); // 11:30 PM
        
        $period = new TimePeriod(
            'Night Shift',
            '22:00', // 10 PM
            '06:00', // 6 AM next day
            true,    // spans_next_day
            0,
            0
        );

        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Time within cross-day period should return true');

        // Test early morning within cross-day period
        Carbon::setTestNow(Carbon::create(2024, 1, 16, 5, 30, 0)); // 5:30 AM next day
        $result = $this->callProtectedMethod($this->service, 'isTimeWithinPeriod', [Carbon::now()->format('H:i'), $period]);
        $this->assertTrue($result, 'Early morning time within cross-day period should return true');

        Carbon::setTestNow();
    }

    /**
     * Test break time limit validation - within acceptable limits
     */
    public function test_break_time_limit_within_acceptable(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['break_duration', 30] // 30 minutes break
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_BREAK_LIMIT],
            ['config', ['max_break_minutes' => 45]] // 45 minutes max
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify no violation was detected
        $this->assertFalse($result);
    }

    /**
     * Test break time limit validation - exceeds limit
     */
    public function test_break_time_limit_exceeds_limit(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['break_duration', 60] // 60 minutes break
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_BREAK_LIMIT],
            ['config', ['max_break_minutes' => 45]] // 45 minutes max
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::TIME_BREAK_LIMIT, $result['constraint_type']);
        $this->assertStringContainsString('break', strtolower($result['message']));
        $this->assertEquals(60, $result['details']['break_duration']);
        $this->assertEquals(45, $result['details']['max_allowed']);
    }

    /**
     * Test late clock out validation - within acceptable limit
     */
    public function test_late_clock_out_within_acceptable(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['scheduled_end', '17:00'],
            ['clock_out', '17:10'] // 10 minutes late
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_LATE_CLOCK_OUT],
            ['config', ['acceptable_delay_minutes' => 15]] // 15 minutes acceptable
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify no violation was detected
        $this->assertFalse($result);
    }

    /**
     * Test late clock out validation - exceeds acceptable limit
     */
    public function test_late_clock_out_exceeds_acceptable(): void
    {
        // Create mock attendance and constraint
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['scheduled_end', '17:00'],
            ['clock_out', '17:30'] // 30 minutes late
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_LATE_CLOCK_OUT],
            ['config', ['acceptable_delay_minutes' => 15]] // 15 minutes acceptable
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::TIME_LATE_CLOCK_OUT, $result['constraint_type']);
        $this->assertStringContainsString('clock out', strtolower($result['message']));
        $this->assertEquals('17:30', $result['details']['clock_out']);
        $this->assertEquals(15, $result['details']['acceptable_delay']);
    }

    /**
     * Test multiple periods validation - time within period
     */
    public function test_multiple_periods_time_within_period(): void
    {
        // Create mock attendance with time within allowed period
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in', '09:15'], // 9:15 AM - within morning period
            ['day_of_week', 'Monday']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_MULTIPLE_PERIODS],
            ['config', [
                'weekly_schedule' => [
                    'Monday' => [
                        'enabled' => true,
                        'periods' => [
                            [
                                'name' => 'Morning',
                                'start_time' => '09:00',
                                'end_time' => '12:00',
                                'spans_next_day' => false,
                                'grace_before_minutes' => 15,
                                'grace_after_minutes' => 15
                            ],
                            [
                                'name' => 'Afternoon',
                                'start_time' => '13:00',
                                'end_time' => '17:00',
                                'spans_next_day' => false,
                                'grace_before_minutes' => 15,
                                'grace_after_minutes' => 15
                            ]
                        ]
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify no violation was detected
        $this->assertFalse($result);
    }

    /**
     * Test multiple periods validation - time outside all periods
     */
    public function test_multiple_periods_time_outside_periods(): void
    {
        // Create mock attendance with time outside of allowed periods
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['clock_in', '12:30'], // 12:30 PM - between periods
            ['day_of_week', 'Monday']
        ]);
        
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_TIME],
            ['subtype', AttendanceConstraint::TIME_MULTIPLE_PERIODS],
            ['config', [
                'weekly_schedule' => [
                    'Monday' => [
                        'enabled' => true,
                        'periods' => [
                            [
                                'name' => 'Morning',
                                'start_time' => '09:00',
                                'end_time' => '12:00',
                                'spans_next_day' => false,
                                'grace_before_minutes' => 15,
                                'grace_after_minutes' => 15
                            ],
                            [
                                'name' => 'Afternoon',
                                'start_time' => '13:00',
                                'end_time' => '17:00',
                                'spans_next_day' => false,
                                'grace_before_minutes' => 15,
                                'grace_after_minutes' => 15
                            ]
                        ]
                    ]
                ]
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateTimeConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::TIME_MULTIPLE_PERIODS, $result['constraint_type']);
        $this->assertStringContainsString('outside allowed periods', $result['message']);
        $this->assertEquals('12:30', $result['details']['clock_in_time']);
    }
}

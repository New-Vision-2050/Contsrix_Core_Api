<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\BehavioralConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;

class BehavioralConstraintServiceTest extends TestCase
{
    private BehavioralConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BehavioralConstraintService();
    }

    /**
     * Test attendance pattern validation with compliant pattern
     */
    public function test_attendance_pattern_validation_compliant(): void
    {
        // Mock user's historical attendances
        $attendanceHistory = [
            ['clock_in' => '09:02', 'clock_out' => '17:05', 'date' => '2023-06-20'],
            ['clock_in' => '09:01', 'clock_out' => '17:10', 'date' => '2023-06-21'],
            ['clock_in' => '08:55', 'clock_out' => '17:00', 'date' => '2023-06-22'],
            ['clock_in' => '09:03', 'clock_out' => '17:15', 'date' => '2023-06-23']
        ];

        // Create mock user with attendance history
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 1],
            ['name', 'John Doe']
        ]);
        
        // Create mock for attendance repository to return history
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserRecentAttendances')->willReturn($attendanceHistory);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['clock_in', '09:05'],
            ['date', '2023-06-24']
        ]);
        
        // Create constraint checking for consistent attendance times
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_PATTERN],
            ['config', [
                'pattern_type' => 'clock_in_consistency',
                'max_deviation_minutes' => 15,
                'required_consistency_days' => 3
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // No violation should be detected when pattern is consistent
        $this->assertFalse($result);
    }

    /**
     * Test attendance pattern validation with non-compliant pattern
     */
    public function test_attendance_pattern_validation_non_compliant(): void
    {
        // Mock user's historical attendances with inconsistent pattern
        $attendanceHistory = [
            ['clock_in' => '09:02', 'clock_out' => '17:05', 'date' => '2023-06-20'],
            ['clock_in' => '09:01', 'clock_out' => '17:10', 'date' => '2023-06-21'],
            ['clock_in' => '08:55', 'clock_out' => '17:00', 'date' => '2023-06-22'],
            ['clock_in' => '09:03', 'clock_out' => '17:15', 'date' => '2023-06-23']
        ];

        // Create mock user with attendance history
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 1],
            ['name', 'John Doe']
        ]);
        
        // Create mock for attendance repository to return history
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserRecentAttendances')->willReturn($attendanceHistory);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance with significantly different time
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['clock_in', '10:30'], // Significantly later than pattern
            ['date', '2023-06-24']
        ]);
        
        // Create constraint checking for consistent attendance times
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_PATTERN],
            ['config', [
                'pattern_type' => 'clock_in_consistency',
                'max_deviation_minutes' => 15,
                'required_consistency_days' => 3
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::BEHAVIORAL_PATTERN, $result['constraint_type']);
        $this->assertStringContainsString('pattern', strtolower($result['message']));
        $this->assertArrayHasKey('expected_time', $result['details']);
        $this->assertArrayHasKey('actual_time', $result['details']);
    }

    /**
     * Test overtime limit validation with acceptable overtime
     */
    public function test_overtime_limit_validation_acceptable(): void
    {
        // Mock user's weekly overtime
        $weeklyWork = [
            ['date' => '2023-06-19', 'regular_hours' => 8, 'overtime_hours' => 1],
            ['date' => '2023-06-20', 'regular_hours' => 8, 'overtime_hours' => 2],
            ['date' => '2023-06-21', 'regular_hours' => 8, 'overtime_hours' => 0],
            ['date' => '2023-06-22', 'regular_hours' => 8, 'overtime_hours' => 1]
        ];

        // Total overtime so far: 4 hours

        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 2],
            ['name', 'Jane Doe']
        ]);
        
        // Create mock for attendance repository to return work hours
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserWeeklyWorkHours')->willReturn($weeklyWork);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance with 2 more overtime hours (total 6, below 10 limit)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['date', '2023-06-23'],
            ['regular_hours', 8],
            ['overtime_hours', 2]
        ]);
        
        // Create constraint with overtime limit
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_OVERTIME_LIMIT],
            ['config', [
                'max_weekly_overtime_hours' => 10
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // No violation should be detected when overtime is within limits
        $this->assertFalse($result);
    }

    /**
     * Test overtime limit validation with excessive overtime
     */
    public function test_overtime_limit_validation_excessive(): void
    {
        // Mock user's weekly overtime
        $weeklyWork = [
            ['date' => '2023-06-19', 'regular_hours' => 8, 'overtime_hours' => 2],
            ['date' => '2023-06-20', 'regular_hours' => 8, 'overtime_hours' => 3],
            ['date' => '2023-06-21', 'regular_hours' => 8, 'overtime_hours' => 2],
            ['date' => '2023-06-22', 'regular_hours' => 8, 'overtime_hours' => 2]
        ];

        // Total overtime so far: 9 hours

        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 2],
            ['name', 'Jane Doe']
        ]);
        
        // Create mock for attendance repository to return work hours
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserWeeklyWorkHours')->willReturn($weeklyWork);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance with 3 more overtime hours (total 12, exceeding 10 limit)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['date', '2023-06-23'],
            ['regular_hours', 8],
            ['overtime_hours', 3]
        ]);
        
        // Create constraint with overtime limit
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_OVERTIME_LIMIT],
            ['config', [
                'max_weekly_overtime_hours' => 10
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::BEHAVIORAL_OVERTIME_LIMIT, $result['constraint_type']);
        $this->assertStringContainsString('overtime', strtolower($result['message']));
        $this->assertEquals(12, $result['details']['total_weekly_overtime']);
        $this->assertEquals(10, $result['details']['max_allowed']);
    }

    /**
     * Test consecutive days validation with acceptable schedule
     */
    public function test_consecutive_days_validation_acceptable(): void
    {
        // Mock user's consecutive workdays (4 days so far)
        $consecutiveWork = [
            ['date' => '2023-06-19', 'has_attendance' => true],
            ['date' => '2023-06-20', 'has_attendance' => true],
            ['date' => '2023-06-21', 'has_attendance' => true],
            ['date' => '2023-06-22', 'has_attendance' => true]
        ];

        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 3],
            ['name', 'Bob Smith']
        ]);
        
        // Create mock for attendance repository to return consecutive work days
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserConsecutiveWorkDays')->willReturn($consecutiveWork);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance (5th consecutive day, below 6 day limit)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['date', '2023-06-23']
        ]);
        
        // Create constraint with consecutive days limit
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_CONSECUTIVE_DAYS],
            ['config', [
                'max_consecutive_workdays' => 6
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // No violation should be detected when consecutive days are within limits
        $this->assertFalse($result);
    }

    /**
     * Test consecutive days validation with excessive schedule
     */
    public function test_consecutive_days_validation_excessive(): void
    {
        // Mock user's consecutive workdays (6 days so far)
        $consecutiveWork = [
            ['date' => '2023-06-17', 'has_attendance' => true],
            ['date' => '2023-06-18', 'has_attendance' => true],
            ['date' => '2023-06-19', 'has_attendance' => true],
            ['date' => '2023-06-20', 'has_attendance' => true],
            ['date' => '2023-06-21', 'has_attendance' => true],
            ['date' => '2023-06-22', 'has_attendance' => true]
        ];

        // Create mock user
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 3],
            ['name', 'Bob Smith']
        ]);
        
        // Create mock for attendance repository to return consecutive work days
        $attendanceRepo = $this->createMock(\Modules\Attendance\Repositories\AttendanceRepositoryInterface::class);
        $attendanceRepo->method('getUserConsecutiveWorkDays')->willReturn($consecutiveWork);
        
        // Set the repository in the service using reflection
        $reflection = new \ReflectionClass($this->service);
        $property = $reflection->getProperty('attendanceRepository');
        $property->setAccessible(true);
        $property->setValue($this->service, $attendanceRepo);
        
        // Create current attendance (7th consecutive day, exceeding 6 day limit)
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['date', '2023-06-23']
        ]);
        
        // Create constraint with consecutive days limit
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_BEHAVIORAL],
            ['subtype', AttendanceConstraint::BEHAVIORAL_CONSECUTIVE_DAYS],
            ['config', [
                'max_consecutive_workdays' => 6
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateBehavioralConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::BEHAVIORAL_CONSECUTIVE_DAYS, $result['constraint_type']);
        $this->assertStringContainsString('consecutive', strtolower($result['message']));
        $this->assertEquals(7, $result['details']['consecutive_days']);
        $this->assertEquals(6, $result['details']['max_allowed']);
    }
}

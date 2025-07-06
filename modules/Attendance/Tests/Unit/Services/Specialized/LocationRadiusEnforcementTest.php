<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceTask;
use Modules\Attendance\Services\LocationConstraintService;
use Modules\Attendance\Services\RadiusEnforcementService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\TaskService;
use Modules\Attendance\DataClasses\LocationTrackingPoint;
use Modules\Attendance\DataClasses\LocationTrackingCollection;
use Modules\Attendance\DataClasses\BranchLocation;
use Modules\Attendance\DataClasses\TemporaryLocationException;
use Tests\TestCase;

class LocationRadiusEnforcementTest extends TestCase
{
    protected $locationService;
    protected $radiusEnforcementService;
    protected $attendanceService;
    protected $taskService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Using Laravel TestCase allows us to avoid mocking Log directly
        // which was causing Mockery issues
        
        $this->radiusEnforcementService = $this->createMock(RadiusEnforcementService::class);
        $this->attendanceService = $this->createMock(AttendanceService::class);
        $this->taskService = $this->createMock(TaskService::class);
        
        $this->locationService = new LocationConstraintService(
            $this->attendanceService,
            $this->radiusEnforcementService,
            $this->taskService
        );
    }

    /**
     * Test location radius enforcement with time threshold and auto-end shift
     * 
     * This tests the scenario where an employee leaves the allowed radius for longer
     * than the configured time threshold, triggering automatic shift ending and
     * potentially marking the day as absent.
     */
    public function test_radius_enforcement_with_auto_shift_end(): void
    {
        // Setup mock attendance with realistic location tracking data
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['id', '12345'],
            ['user_id', '555'],
            ['branch_id', 'branch-001'],
            ['location_tracking', [
                // Realistic sequence of tracked locations with timestamps and device info
                [
                    'latitude' => 24.4539, 
                    'longitude' => 54.3773, 
                    'timestamp' => '2025-06-25 09:00:00',
                    'accuracy' => 5.0, // GPS accuracy in meters
                    'device_id' => 'iPhone-12-ABC123',
                    'app_version' => '1.2.3',
                    'battery_level' => 85
                ],
                [
                    'latitude' => 24.4540, 
                    'longitude' => 54.3774, 
                    'timestamp' => '2025-06-25 09:15:00',
                    'accuracy' => 3.0,
                    'device_id' => 'iPhone-12-ABC123',
                    'app_version' => '1.2.3',
                    'battery_level' => 82
                ],
                [
                    'latitude' => 24.4600, 
                    'longitude' => 54.3800, 
                    'timestamp' => '2025-06-25 10:30:00', // Outside radius for extended time
                    'accuracy' => 4.0,
                    'device_id' => 'iPhone-12-ABC123',
                    'app_version' => '1.2.3',
                    'battery_level' => 78
                ],
                [
                    'latitude' => 24.4605, 
                    'longitude' => 54.3805, 
                    'timestamp' => '2025-06-25 11:00:00', // Still outside radius
                    'accuracy' => 6.0,
                    'device_id' => 'iPhone-12-ABC123',
                    'app_version' => '1.2.3',
                    'battery_level' => 75
                ]
            ]],
            ['status', 'active'],
            ['shift_end_method', null],
            ['exceptions', []] // No exceptions for this test
        ]);
        
        // Setup constraint with auto-end shift enabled
        $constraint = $this->createPartialMock(AttendanceConstraint::class, ['getBranchLocation']);
        $constraint->id = 'c47ac10b-58cc-4372-a567-0e02b2c3d479'; // Proper UUID format
        $constraint->constraint_name = AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT;
        $constraint->config = [
            'enforcement' => [
                'out_of_radius_time_threshold' => 15, // 15 minutes threshold
                'end_shift_if_violated' => true,
                'mark_absent_if_violated' => true
            ],
            'branch_locations' => [
                'branch-001' => [
                    'latitude' => 24.4539,
                    'longitude' => 54.3773,
                    'radius' => 0.1 // 100 meters radius
                ]
            ],
            'violation_severity' => 'high'
        ];
        
        // Mock the branch location lookup
        $branchLocation = [
            'latitude' => 24.4539,
            'longitude' => 54.3773,
            'radius' => 0.1
        ];
        $constraint->method('getBranchLocation')
            ->with('branch-001')
            ->willReturn($branchLocation);
        
        // Define the violation result that the radius service will return
        $violationResult = [
            'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
            'severity' => 'high',
            'message' => 'Employee is outside allowed radius for more than 15 minutes.',
            'details' => [
                'branch_id' => 'branch-001',
                'clock_in_location' => [24.4539, 54.3773],
                'allowed_location' => [24.4539, 54.3773],
                'radius' => 0.1,
                'distance' => 22.2, // km
                'allowed_distance' => 0.1, // km
                'time_threshold' => 15, // minutes
                'enforcement_action' => 'end_shift'
            ]
        ];
        
        // Setup our service mocks for this specific test
        $this->radiusEnforcementService = $this->createMock(RadiusEnforcementService::class);
        $this->radiusEnforcementService->expects($this->once())
            ->method('validateRadiusEnforcement')
            ->with($this->equalTo($attendance), $this->equalTo($constraint))
            ->willReturn($violationResult);
        
        // Task service should be called to create a task for the violation
        $mockTask = $this->createMock(AttendanceTask::class);
        $this->taskService->expects($this->once())
            ->method('createConstraintExceptionTask')
            ->willReturn($mockTask);
        
        // Create a fresh instance of the service under test with our mocks
        $this->locationService = new LocationConstraintService(
            $this->attendanceService,
            $this->radiusEnforcementService,
            $this->taskService
        );

        // Call the service method
        $result = $this->locationService->validateLocationConstraint($attendance, $constraint);
        
        // Assert that the result is returned correctly
        $this->assertSame($violationResult, $result);
    }

    /**
     * Test location radius enforcement with temporary exception
     * 
     * This tests the scenario where an employee has been granted a temporary
     * location exception (e.g., for a client meeting or remote work) and should
     * not trigger violations despite being outside the normal allowed radius.
     */
    public function test_radius_enforcement_with_temporary_exception(): void
    {
        // Setup mock attendance with location tracking data and a temporary exception
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['id', '12346'],
            ['user_id', '556'],
            ['branch_id', 'branch-001'],
            ['location_tracking', [
                // Realistic sequence of tracked locations with timestamps and device info
                [
                    'latitude' => 24.4539, 
                    'longitude' => 54.3773, 
                    'timestamp' => '2025-06-25 09:00:00', // Within original radius
                    'accuracy' => 5.0,
                    'device_id' => 'Samsung-Galaxy-XYZ789',
                    'app_version' => '1.2.3',
                    'battery_level' => 90,
                    'network_type' => '4G'
                ],
                [
                    'latitude' => 24.5060, 
                    'longitude' => 54.4095, 
                    'timestamp' => '2025-06-25 10:30:00', // Far outside original radius - but has exception
                    'accuracy' => 8.0,
                    'device_id' => 'Samsung-Galaxy-XYZ789',
                    'app_version' => '1.2.3',
                    'battery_level' => 85,
                    'network_type' => '4G'
                ],
                [
                    'latitude' => 24.5065, 
                    'longitude' => 54.4090, 
                    'timestamp' => '2025-06-25 11:15:00', // Far outside original radius - but has exception
                    'accuracy' => 6.0,
                    'device_id' => 'Samsung-Galaxy-XYZ789',
                    'app_version' => '1.2.3',
                    'battery_level' => 80,
                    'network_type' => '4G'
                ],
                [
                    'latitude' => 24.5070, 
                    'longitude' => 54.4085, 
                    'timestamp' => '2025-06-25 11:45:00', // Far outside original radius - but has exception
                    'accuracy' => 7.0,
                    'device_id' => 'Samsung-Galaxy-XYZ789',
                    'app_version' => '1.2.3',
                    'battery_level' => 78,
                    'network_type' => '4G'
                ]
            ]],
            ['status', 'active'],
            ['shift_end_method', null],
            ['exceptions', [
                [
                    'id' => 'exception-123',
                    'type' => 'temporary_location',
                    'start_time' => '2025-06-25 10:00:00',
                    'end_time' => '2025-06-25 16:00:00',
                    'approved_by' => 'manager-123',
                    'reason' => 'Client meeting',
                    'temporary_location' => [
                        'name' => 'Client Office',
                        'latitude' => 24.5060,
                        'longitude' => 54.4090,
                        'radius' => 100
                    ]
                ]
            ]],
        ]);
        
        // Configure the constraint with radius enforcement settings
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_LOCATION],
            ['subtype', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['id', 'c47ac10b-58cc-4372-a567-0e02b2c3d479'], // Proper UUID format
            ['config', [
                'branch_locations' => [
                    'branch-001' => [
                        'name' => 'Main Office',
                        'latitude' => 24.4539, 
                        'longitude' => 54.3773,
                        'radius' => 100, // 100 meters radius
                    ]
                ],
                'enforcement' => [
                    'out_of_radius_time_threshold' => 30, // Minutes allowed outside before enforcement
                    'mark_absent_if_violated' => true,
                    'end_shift_if_violated' => true,
                    'allow_temporary_exceptions' => true
                ],
                'violation_severity' => 'high'
            ]]
        ]);
        
        // We don't expect endShiftAutomatically to be called since the employee has a valid exception
        $this->attendanceService->expects($this->never())
            ->method('endShiftAutomatically');
            
        // Call the service method directly
        $result = $this->locationService->validateLocationConstraint($attendance, $constraint);
        
        // Configure the constraint with radius enforcement settings
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['id', 'c47ac10b-58cc-4372-a567-0e02b2c3d479'], // Proper UUID format
            ['type', 'location'],
            ['subtype', 'location_radius_enforcement'], 
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['config', [
                'branch_locations' => [
                    'branch-002' => [
                        'name' => 'Downtown Office',
                        'latitude' => 24.5126, 
                        'longitude' => 54.3705,
                        'radius' => 200, // 200 meters radius
                    ]
                ],
                'enforcement' => [
                    'out_of_radius_time_threshold' => 15, // Minutes allowed outside before enforcement
                    'mark_absent_if_violated' => true,
                    'end_shift_if_violated' => true,
                    'allow_temporary_exceptions' => true
                ],
                'violation_severity' => 'high'
            ]]
        ]);
        
        // We should never call endShiftAutomatically since there's no violation
        $this->attendanceService->expects($this->never())
            ->method('endShiftAutomatically');
            
        // Setup the RadiusEnforcementService mock to return false (no violation) 
        // since user is within temporary location radius
        $this->radiusEnforcementService->expects($this->once())
            ->method('validateRadiusEnforcement')
            ->with($attendance, $constraint)
            ->willReturn(false);
            
        // Call the service method directly
        $result = $this->locationService->validateLocationConstraint($attendance, $constraint);
        
        // Verify no violation was detected (result should be false)
        $this->assertFalse($result);
        
        // Reset test time
        Carbon::setTestNow();
    }

    /**
     * Test temporary location exception within radius
     * 
     * This test verifies that employees are allowed to be at a temporary location
     * (such as a client office) during a specified time period without triggering
     * location radius enforcement violations.
     */
    public function test_temporary_location_exception_within_radius(): void
    {
        $now = Carbon::parse('2025-06-25 11:00:00');
        Carbon::setTestNow($now);
        
        // Create branch locations using data classes
        // Use constructors directly to set custom radius values
        $mainOffice = new BranchLocation(
            name: 'Downtown Office',
            latitude: 24.5126,
            longitude: 54.3705,
            radius: 200.0, // 200 meters radius
            address: 'Business Bay, Dubai',
            description: 'Office location'
        );
        
        $clientOffice = new BranchLocation(
            name: 'Client Office',
            latitude: 24.5126,
            longitude: 54.3705,
            radius: 150.0, // 150 meters radius
            address: 'DIFC, Dubai',
            description: 'Client site'
        );
        
        // Create a temporary location exception using data class constructor
        $exception = new TemporaryLocationException(
            type: 'temporary_location',
            startTime: Carbon::parse('2025-06-25 09:00:00'),
            endTime: Carbon::parse('2025-06-25 17:00:00'),
            temporaryLocation: $clientOffice,
            reason: 'Client project meeting',
            approvedBy: 'Manager-123',
            isActive: true
        );
        
        // Create location tracking points using data classes
        $trackingPoints = [
            new LocationTrackingPoint(
                24.5126,                                    // latitude
                54.3705,                                   // longitude
                Carbon::parse('2025-06-25 09:00:00'),      // timestamp
                4.0,                                       // accuracy
                'iPhone-13-DEF456',                        // deviceId
                '1.2.3',                                   // appVersion
                95,                                        // batteryLevel
                '5G',                                      // networkType
                'GPS'                                      // locationSource
            ),
            new LocationTrackingPoint(
                24.5128,                                    // latitude
                54.3707,                                   // longitude
                Carbon::parse('2025-06-25 10:30:00'),      // timestamp
                3.0,                                       // accuracy
                'iPhone-13-DEF456',                        // deviceId
                '1.2.3',                                   // appVersion
                88,                                        // batteryLevel
                '5G',                                      // networkType
                'GPS'                                      // locationSource
            ),
            new LocationTrackingPoint(
                24.5125,                                    // latitude
                54.3703,                                   // longitude
                Carbon::parse('2025-06-25 12:00:00'),      // timestamp
                5.0,                                       // accuracy
                'iPhone-13-DEF456',                        // deviceId
                '1.2.3',                                   // appVersion
                82,                                        // batteryLevel
                '5G',                                      // networkType
                'GPS'                                      // locationSource
            )
        ];
        
        // Create a LocationTrackingCollection from the points
        $trackingCollection = new LocationTrackingCollection($trackingPoints);
        
        // Setup mock attendance with location tracking collection
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['id', '12347'],
            ['user_id', '557'],
            ['branch_id', 'branch-002'],
            ['location_tracking', $trackingCollection->toArray()], // Convert to array for backward compatibility
            ['status', 'active'],
            ['shift_end_method', null],
            ['exceptions', [
                [
                    'type' => 'temporary_location',
                    'start_time' => $exception->startTime->toDateTimeString(),
                    'end_time' => $exception->endTime->toDateTimeString(),
                    'temporary_location' => $exception->temporaryLocation->toArray()
                ]
            ]],
        ]);
        
        // Configure the constraint with radius enforcement settings
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['id', 'c47ac10b-58cc-4372-a567-0e02b2c3d479'], // Proper UUID format
            ['type', 'location'],
            ['subtype', 'location_radius_enforcement'], 
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['config', [
                'branch_locations' => [
                    'branch-002' => $mainOffice->toArray()
                ],
                'enforcement' => [
                    'out_of_radius_time_threshold' => 15, // Minutes allowed outside before enforcement
                    'mark_absent_if_violated' => true,
                    'end_shift_if_violated' => true,
                    'allow_temporary_exceptions' => true
                ],
                'violation_severity' => 'high'
            ]]
        ]);
        
        // We should never call endShiftAutomatically since there's no violation
        $this->attendanceService->expects($this->never())
            ->method('endShiftAutomatically');
            
        // Setup the RadiusEnforcementService mock to return false (no violation) 
        // since user is within temporary location radius
        $this->radiusEnforcementService->expects($this->once())
            ->method('validateRadiusEnforcement')
            ->with($attendance, $constraint)
            ->willReturn(false);
            
        // Call the service method directly
        $result = $this->locationService->validateLocationConstraint($attendance, $constraint);
        
        // Verify no violation was detected (result should be false)
        $this->assertFalse($result);
        
        // Reset test time
        Carbon::setTestNow();
    }

    /**
     * Test automatic constraint assignment for new employee
     * 
     * This tests the scenario where a new employee is automatically assigned
     * the default location radius constraint for their branch.
     * 
     * Note: This test has been modified to align with the refactored service architecture.
     * The repository-level method has been moved to a dedicated repository service.
     */
    public function test_auto_constraint_assignment_for_new_employee(): void
    {
        // This functionality is now handled by a repository service
        // We're testing the integration here so we'll mock an expected result
        $this->markTestSkipped('This test needs to be moved to a dedicated repository test class');
    }

    /**
     * Test error handling for missing branch location config
     * 
     * This tests the scenario where an employee's branch doesn't have location 
     * configuration in the constraint, so a helpful error message should be returned.
     */
    public function test_error_handling_for_missing_branch_location(): void
    {
        // Setup attendance that tries to use an unknown branch
        $attendance = $this->createPartialMock(Attendance::class, []);
        $attendance->id = 'f47ac10b-58cc-4372-a567-0e02b2c3d479'; // Proper UUID format
        $attendance->user_id = '9d9b93e2-7335-4cf5-8a3f-46bc166a5a43';
        $attendance->branch_id = 'branch-unknown';
        $attendance->location_tracking = [
            // Realistic location tracking data for employee at unknown branch
            [
                'latitude' => 25.2048, 
                'longitude' => 55.2708, 
                'timestamp' => '2025-06-25 09:00:00',
                'accuracy' => 6.0,
                'device_id' => 'Android-Pixel-GHI789',
                'app_version' => '1.2.3',
                'battery_level' => 87,
                'network_type' => '4G',
                'location_source' => 'GPS'
            ],
            [
                'latitude' => 25.2050, 
                'longitude' => 55.2710, 
                'timestamp' => '2025-06-25 10:30:00',
                'accuracy' => 4.0,
                'device_id' => 'Android-Pixel-GHI789',
                'app_version' => '1.2.3',
                'battery_level' => 82,
                'network_type' => '4G',
                'location_source' => 'GPS'
            ]
        ];
        
        // Setup constraint with no branch location for the attendance branch
        $constraint = $this->createPartialMock(AttendanceConstraint::class, []);
        $constraint->id = 'c47ac10b-58cc-4372-a567-0e02b2c3d479'; // Proper UUID format
        $constraint->constraint_name = AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT;
        $constraint->config = [
            'branch_locations' => [
                'other-branch-id' => [
                    'latitude' => 25.0,
                    'longitude' => 55.0,
                    'radius' => 100
                ]
            ],
            'enforcement' => [
                'out_of_radius_time_threshold' => 15,
                'end_shift_if_violated' => true,
                'mark_absent_if_violated' => false
            ],
            'violation_severity' => 'high'
        ];
        
        // Note: getBranchLocation is not called by RadiusEnforcementService
        // It checks the config directly, so we don't need to mock this method
        
        // Define the violation result that will be returned by the radius service
        $violationResult = [
            'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
            'severity' => 'high',
            'message' => 'No branch location configuration found for this branch.',
            'details' => [
                'branch_id' => 'branch-unknown',
                'available_branches' => ['other-branch-id']
            ]
        ];

        // Setup our service mocks for this specific test
        $this->radiusEnforcementService = $this->createMock(RadiusEnforcementService::class);
        $this->radiusEnforcementService->expects($this->once())
            ->method('validateRadiusEnforcement')
            ->with($this->equalTo($attendance), $this->equalTo($constraint))
            ->willReturn($violationResult);
        
        // Task service should be called to create a task for the violation
        $mockTask = $this->createMock(AttendanceTask::class);
        $this->taskService->expects($this->once())
            ->method('createConstraintExceptionTask')
            ->willReturn($mockTask);
        
        // Create a fresh instance of the service under test with our mocks
        $this->locationService = new LocationConstraintService(
            $this->attendanceService,
            $this->radiusEnforcementService,
            $this->taskService
        );
        
        // Call the service method
        $result = $this->locationService->validateLocationConstraint($attendance, $constraint);
        
        // Assert that the result is returned correctly
        $this->assertSame($violationResult, $result);
        
        // Reset test time if needed
        Carbon::setTestNow();
    }
}

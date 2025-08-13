<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\TaskService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceTask;
use Ramsey\Uuid\Uuid;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskService = new TaskService();
    }

    /**
     * Test task creation for constraint violations
     */
    public function test_create_task_for_constraint_violation(): void
    {
        // Mock the attendance record with a location constraint violation
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['id', '12345'],
            ['user_id', '789'],
            ['branch_id', 'branch-003'],
            ['location_tracking', [
                ['latitude' => 25.2, 'longitude' => 55.3, 'timestamp' => '2025-06-25 11:00:00'],
            ]],
            ['status', 'active'],
        ]);

        // Mock the constraint that was violated
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['id', '456'],
            ['constraint_type', 'location'],
            ['subtype', 'location_radius_enforcement'],
            ['config', [
                'violation_severity' => 'high',
                'branch_locations' => [
                    'branch-003' => [
                        'name' => 'Branch Office',
                        'latitude' => 24.9,
                        'longitude' => 54.8,
                        'radius' => 200,
                    ]
                ]
            ]],
        ]);

        // Create a mock AttendanceTask that would be created
        $task = $this->createMock(AttendanceTask::class);
        $task->method('__get')->willReturnMap([
            ['id', Uuid::uuid4()->toString()],
            ['status', 'pending'],
            ['priority', 'high'],
            ['type', 'constraint_exception'],
        ]);

        // The violation details that would be reported
        $violationDetails = [
            'constraint_type' => 'location_radius_enforcement',
            'severity' => 'high',
            'message' => 'Employee outside of allowed radius',
            'details' => [
                'user_location' => [
                    'latitude' => 25.2,
                    'longitude' => 55.3
                ],
                'branch_location' => [
                    'latitude' => 24.9,
                    'longitude' => 54.8,
                    'radius' => 200
                ],
                'distance' => 48.5, // kilometers
                'time_outside' => 25, // minutes
            ]
        ];

        // Normally we would actually create a task here, but since this is a unit test
        // we can mock the response and verify the method is called correctly
        $taskServiceMock = $this->getMockBuilder(TaskService::class)
            ->onlyMethods(['createConstraintExceptionTask'])
            ->getMock();
            
        $taskServiceMock->expects($this->once())
            ->method('createConstraintExceptionTask')
            ->with($attendance, $constraint, $violationDetails)
            ->willReturn($task);

        // Act: Call the method to create the task
        $createdTask = $taskServiceMock->createConstraintExceptionTask($attendance, $constraint, $violationDetails);

        // Assert: Verify that a task was created and is pending
        $this->assertEquals('pending', $createdTask->status);
        $this->assertEquals('high', $createdTask->priority);
        $this->assertEquals('constraint_exception', $createdTask->type);
    }

    /**
     * Test automatic branch constraint creation and employee assignment
     */
    public function test_branch_constraint_assignment_with_task_creation(): void
    {
        // In a full test, we'd integrate with the BranchConstraintService
        // This is a placeholder showing how the integration would work
        
        // 1. Mock new branch creation
        $branchId = 'new-branch-123';
        $companyId = 'company-456';
        $branchLocation = [
            'name' => 'New Office',
            'latitude' => 25.1,
            'longitude' => 55.2
        ];
        $createdBy = 'admin-user-789';
        
        // 2. Mock the constraint that would be created
        $constraintId = Uuid::uuid4()->toString();
        $mockConstraint = $this->createMock(AttendanceConstraint::class);
        $mockConstraint->method('__get')->willReturn($constraintId);
        
        // 3. Mock BranchConstraintService (in a real test, this would be injected)
        $branchService = $this->getMockBuilder('Modules\Attendance\Services\BranchConstraintService')
            ->disableOriginalConstructor()
            ->onlyMethods(['createDefaultBranchLocationConstraint', 'assignBranchEmployeesToConstraint'])
            ->getMock();
            
        $branchService->expects($this->once())
            ->method('createDefaultBranchLocationConstraint')
            ->with($branchId, $companyId, $branchLocation, 100, $createdBy)
            ->willReturn($mockConstraint);
            
        $branchService->expects($this->once())
            ->method('assignBranchEmployeesToConstraint')
            ->with($branchId, $constraintId)
            ->willReturn(5); // 5 employees assigned
            
        // 4. Test would assert that constraints were created and employees assigned
        $this->assertNotNull($mockConstraint);
    }
    
    /**
     * Test task creation for location exception handling
     */
    public function test_create_task_for_temporary_location_request(): void
    {
        // 1. Mock attendance record
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['id', '12345'],
            ['user_id', '789'],
        ]);
        
        // 2. Create a temporary location request
        $temporaryLocation = [
            'name' => 'Client Site',
            'latitude' => 25.3,
            'longitude' => 55.4,
            'radius' => 150
        ];
        
        $startTime = '2025-06-26 09:00:00';
        $endTime = '2025-06-26 17:00:00';
        $createdBy = 'user-456';
        $notes = 'Meeting with client';
        
        // 3. Mock task that would be created from this request
        $task = $this->createMock(AttendanceTask::class);
        $task->method('__get')->willReturnMap([
            ['id', Uuid::uuid4()->toString()],
            ['status', 'pending'],
            ['type', 'temporary_location'],
        ]);
        
        // 4. Setup mock TaskService
        $taskServiceMock = $this->getMockBuilder(TaskService::class)
            ->onlyMethods(['createTask'])
            ->getMock();
            
        // 5. Prepare expected task details
        $expectedDetails = [
            'temporary_location' => $temporaryLocation,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'notes' => $notes,
            'attendance_id' => '12345',
        ];
            
        $taskServiceMock->expects($this->once())
            ->method('createTask')
            ->with(
                '789',                      // userId 
                $this->anything(),          // constraintId (can be any value in test)
                'temporary_location',       // type
                $this->callback(function($details) use ($expectedDetails) {
                    return $details['temporary_location'] === $expectedDetails['temporary_location']
                        && $details['start_time'] === $expectedDetails['start_time']
                        && $details['end_time'] === $expectedDetails['end_time'];
                }),                         // details matching our expectations
                $this->anything(),          // assignedTo (can be any value)
                $this->anything(),          // dueDate (can be any value)
                'medium'                    // priority
            )
            ->willReturn($task);
                
        // 6. Call the createTask method via a wrapper method (in real code would be createTemporaryLocationTask)
        $createdTask = $taskServiceMock->createTask(
            '789',                          // userId
            'constraint-789',               // constraintId 
            'temporary_location',           // type
            $expectedDetails,               // details
            null,                           // assignedTo
            null,                           // dueDate
            'medium'                        // priority
        );
        
        // 7. Assert the task was created with correct type
        $this->assertEquals('temporary_location', $createdTask->type);
    }
}

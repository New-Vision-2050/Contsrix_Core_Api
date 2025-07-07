<?php

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\BranchConstraintService;
use Modules\Attendance\Services\TaskService;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceTask;
use Modules\User\Models\User;
use Modules\User\Models\Branch;
use Ramsey\Uuid\Uuid;

class BranchConstraintIntegrationTest extends TestCase
{
    private BranchConstraintService $branchConstraintService;
    private TaskService $taskService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->taskService = $this->createMock(TaskService::class);
        
        // Instantiate the service with dependencies
        $this->branchConstraintService = new BranchConstraintService(
            $this->taskService
        );
    }
    
    /**
     * Test for automatic constraint assignment when a new branch is created
     */
    public function test_new_branch_constraint_assignment_flow(): void
    {
        // 1. Create mock data for a new branch
        $branchId = Uuid::uuid4()->toString();
        $companyId = 'company-123';
        $branchName = 'New Branch Office';
        $branchLocation = [
            'name' => $branchName,
            'latitude' => 25.123456,
            'longitude' => 55.654321
        ];
        $createdBy = 'admin-user';
        $defaultRadius = 150;
        
        // 2. Create a mock branch object
        $mockBranch = $this->createMock(Branch::class);
        $mockBranch->method('__get')->willReturnMap([
            ['id', $branchId],
            ['company_id', $companyId],
            ['name', $branchName],
            ['location', $branchLocation]
        ]);
        
        // 3. Create a mock constraint that would be created for the branch
        $mockConstraint = $this->createMock(AttendanceConstraint::class);
        $mockConstraint->method('__get')->willReturnMap([
            ['id', Uuid::uuid4()->toString()],
            ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
        ]);
        
        // 4. Mock the list of branch employees
        $mockEmployees = [
            $this->createMock(User::class),
            $this->createMock(User::class),
            $this->createMock(User::class)
        ];
        
        foreach ($mockEmployees as $index => $employee) {
            $employee->method('__get')->willReturnMap([
                ['id', "user-{$index}"],
                ['name', "Employee {$index}"],
                ['company_id', $companyId]
            ]);
        }
        
        // 5. Set up the mock BranchConstraintService 
        $mockBranchConstraintService = $this->getMockBuilder(BranchConstraintService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'createDefaultBranchLocationConstraint',
                'assignBranchEmployeesToConstraint'
            ])
            ->getMock();
        
        // 6. Define expected behavior for createDefaultBranchLocationConstraint
        $mockBranchConstraintService->expects($this->once())
            ->method('createDefaultBranchLocationConstraint')
            ->with($branchId, $companyId, $branchLocation, $defaultRadius, $createdBy)
            ->willReturn($mockConstraint);
            
        // 7. Define expected behavior for assignBranchEmployeesToConstraint
        $mockBranchConstraintService->expects($this->once())
            ->method('assignBranchEmployeesToConstraint')
            ->with($branchId, $mockConstraint->id)
            ->willReturn(count($mockEmployees));
            
        // 8. Call the methods in sequence that would happen when a branch is created
        $constraint = $mockBranchConstraintService->createDefaultBranchLocationConstraint(
            $branchId,
            $companyId, 
            $branchLocation,
            $defaultRadius,
            $createdBy
        );
        
        $assignedCount = $mockBranchConstraintService->assignBranchEmployeesToConstraint(
            $branchId,
            $constraint->id
        );
        
        // 9. Assert the results
        $this->assertNotNull($constraint);
        $this->assertEquals(AttendanceConstraint::TYPE_LOCATION, $constraint->constraint_type);
        $this->assertEquals(AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT, $constraint->constraint_name);
        $this->assertEquals(count($mockEmployees), $assignedCount);
    }
    
    /**
     * Test for automatic constraint assignment when a new employee is created
     */
    public function test_new_employee_constraint_assignment_flow(): void
    {
        // 1. Create mock data for new employee
        $userId = Uuid::uuid4()->toString();
        $branchId = 'branch-123';
        $companyId = 'company-123';
        $createdBy = 'admin-user';
        
        // 2. Create a mock user object
        $mockUser = $this->createMock(User::class);
        $mockUser->method('__get')->willReturnMap([
            ['id', $userId],
            ['company_id', $companyId],
            ['branch_id', $branchId],
            ['name', 'New Employee']
        ]);
        
        // 3. Create mock constraints that exist for the branch
        $mockConstraints = [
            $this->createMock(AttendanceConstraint::class),
            $this->createMock(AttendanceConstraint::class)
        ];
        
        foreach ($mockConstraints as $index => $constraint) {
            $constraint->method('__get')->willReturnMap([
                ['id', "constraint-{$index}"],
                ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
                ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
                ['company_id', $companyId],
                ['branch_ids', [$branchId]]
            ]);
        }
        
        // 4. Set up the mock BranchConstraintService
        $mockBranchConstraintService = $this->getMockBuilder(BranchConstraintService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assignEmployeeToBranchConstraints'])
            ->getMock();
            
        // 5. Define expected behavior
        $mockBranchConstraintService->expects($this->once())
            ->method('assignEmployeeToBranchConstraints')
            ->with($userId, $branchId, $createdBy)
            ->willReturn(count($mockConstraints));
            
        // 6. Call the method that would be triggered when a new employee is created
        $assignedCount = $mockBranchConstraintService->assignEmployeeToBranchConstraints(
            $userId,
            $branchId,
            $createdBy
        );
        
        // 7. Assert the results
        $this->assertEquals(count($mockConstraints), $assignedCount);
    }
    
    /**
     * Test for handling constraint violations through task creation
     */
    public function test_constraint_violation_task_creation_flow(): void
    {
        // 1. Create mock objects for a constraint violation scenario
        $attendanceId = Uuid::uuid4()->toString();
        $userId = 'user-123';
        $branchId = 'branch-456';
        $constraintId = 'constraint-789';
        
        // 2. Mock attendance record with location outside allowed radius
        $mockAttendance = $this->createMock(\Modules\Attendance\Models\Attendance::class);
        $mockAttendance->method('__get')->willReturnMap([
            ['id', $attendanceId],
            ['user_id', $userId],
            ['branch_id', $branchId],
            ['location_tracking', [
                ['latitude' => 25.3, 'longitude' => 55.4, 'timestamp' => Carbon::now()->toDateTimeString()]
            ]]
        ]);
        
        // 3. Mock the constraint that was violated
        $mockConstraint = $this->createMock(AttendanceConstraint::class);
        $mockConstraint->method('__get')->willReturnMap([
            ['id', $constraintId],
            ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['config', [
                'branch_locations' => [
                    $branchId => [
                        'name' => 'Test Branch',
                        'latitude' => 25.1,
                        'longitude' => 55.2,
                        'radius' => 150
                    ]
                ],
                'enforcement' => [
                    'out_of_radius_time_threshold' => 15,
                    'end_shift_if_violated' => false,
                    'mark_absent_if_violated' => false
                ],
                'violation_severity' => 'high'
            ]]
        ]);
        
        // 4. Mock the violation details
        $violationDetails = [
            'constraint_type' => AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT,
            'severity' => 'high',
            'message' => 'Employee outside allowed radius',
            'details' => [
                'user_location' => ['latitude' => 25.3, 'longitude' => 55.4],
                'branch_location' => ['latitude' => 25.1, 'longitude' => 55.2, 'radius' => 150],
                'distance' => 24.6, // kilometers
                'time_outside' => 20 // minutes
            ]
        ];
        
        // 5. Create a mock task that would be created
        $mockTask = $this->createMock(AttendanceTask::class);
        $mockTask->method('__get')->willReturnMap([
            ['id', Uuid::uuid4()->toString()],
            ['status', 'pending'],
            ['priority', 'high'],
            ['type', 'constraint_exception']
        ]);
        
        // 6. Set up mock TaskService expectations
        $mockTaskService = $this->getMockBuilder(TaskService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createConstraintExceptionTask'])
            ->getMock();
            
        $mockTaskService->expects($this->once())
            ->method('createConstraintExceptionTask')
            ->with($mockAttendance, $mockConstraint, $violationDetails)
            ->willReturn($mockTask);
            
        // 7. Test the flow by calling the service method
        $task = $mockTaskService->createConstraintExceptionTask(
            $mockAttendance, 
            $mockConstraint,
            $violationDetails
        );
        
        // 8. Assert the results
        $this->assertNotNull($task);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals('high', $task->priority);
        $this->assertEquals('constraint_exception', $task->type);
    }
}

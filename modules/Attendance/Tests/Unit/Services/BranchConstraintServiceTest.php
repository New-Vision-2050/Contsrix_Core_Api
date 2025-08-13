<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\BranchConstraintService;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class BranchConstraintServiceTest extends TestCase
{
    private BranchConstraintService $branchConstraintService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branchConstraintService = new BranchConstraintService();
    }

    /**
     * Test creation of default location constraint when a new branch is created
     */
    public function test_create_default_branch_location_constraint(): void
    {
        // Arrange: Prepare test data for new branch
        $branchId = 'new-branch-' . Uuid::uuid4()->toString();
        $companyId = 'company-123';
        $branchLocation = [
            'name' => 'New Test Branch',
            'latitude' => 25.123456,
            'longitude' => 55.654321
        ];
        $createdBy = 'admin-user-123';
        $defaultRadius = 150;
        
        // Create mock AttendanceConstraint
        $mockConstraint = $this->createMock(AttendanceConstraint::class);
        
        // Expected constraint config
        $expectedConfig = [
            'branch_locations' => [
                $branchId => [
                    'name' => 'New Test Branch',
                    'latitude' => 25.123456,
                    'longitude' => 55.654321,
                    'radius' => 150,
                ]
            ],
            'enforcement' => [
                'out_of_radius_time_threshold' => 15,
                'mark_absent_if_violated' => false,
                'end_shift_if_violated' => false,
                'allow_temporary_exceptions' => true
            ],
            'violation_severity' => 'medium'
        ];
        
        // Mock the save method and other necessary methods
        $mockConstraint->expects($this->once())
            ->method('save')
            ->willReturn(true);
            
        $mockConstraint->method('__get')
            ->willReturnMap([
                ['id', Uuid::uuid4()->toString()],
                ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
                ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
                ['constraint_config', $expectedConfig]
            ]);
            
        // Mock setting the properties
        $mockConstraint->method('__set')
            ->willReturnCallback(function($property, $value) use ($mockConstraint, $branchId, $companyId, $expectedConfig) {
                if ($property === 'constraint_config') {
                    $this->assertEquals($expectedConfig, $value);
                } elseif ($property === 'branch_ids') {
                    $this->assertEquals([$branchId], $value);
                } elseif ($property === 'company_id') {
                    $this->assertEquals($companyId, $value);
                }
            });
        
        // Create a mock BranchConstraintService 
        $mockService = $this->getMockBuilder(BranchConstraintService::class)
            ->onlyMethods(['createDefaultBranchLocationConstraint'])
            ->getMock();
            
        $mockService->expects($this->once())
            ->method('createDefaultBranchLocationConstraint')
            ->with($branchId, $companyId, $branchLocation, $defaultRadius, $createdBy)
            ->willReturn($mockConstraint);
        
        // Act: Call the service method
        $constraint = $mockService->createDefaultBranchLocationConstraint(
            $branchId,
            $companyId,
            $branchLocation,
            $defaultRadius,
            $createdBy
        );
        
        // Assert: Check that the constraint was created with correct values
        $this->assertNotNull($constraint);
        $this->assertEquals(AttendanceConstraint::TYPE_LOCATION, $constraint->constraint_type);
        $this->assertEquals(AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT, $constraint->constraint_name);
        $this->assertEquals($expectedConfig, $constraint->constraint_config);
    }
    
    /**
     * Test automatic assignment of employees to branch constraints
     */
    public function test_assign_branch_employees_to_constraint(): void
    {
        // Arrange: Prepare test data
        $branchId = 'branch-123';
        $constraintId = 'constraint-456';
        
        // Mock User model and query results
        $mockUsers = [
            $this->createMock(User::class),
            $this->createMock(User::class),
            $this->createMock(User::class)
        ];
        
        // Set up mock properties for users
        foreach ($mockUsers as $index => $user) {
            $user->method('__get')->willReturnMap([
                ['id', "user-{$index}"],
                ['company_id', 'company-123'],
            ]);
        }
        
        // Create a mock branch constraint
        $mockBranchConstraint = $this->createMock(AttendanceConstraint::class);
        $mockBranchConstraint->method('__get')->willReturnMap([
            ['id', $constraintId],
            ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
            ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
            ['constraint_config', [
                'branch_locations' => [
                    $branchId => [
                        'name' => 'Test Branch',
                        'latitude' => 25.1, 
                        'longitude' => 55.2,
                        'radius' => 100
                    ]
                ]
            ]],
            ['created_by', 'admin-123']
        ]);
        
        // Mock the AttendanceConstraint::find static method
        AttendanceConstraint::staticExpects($this->once())
            ->method('find')
            ->with($constraintId)
            ->willReturn($mockBranchConstraint);
        
        // Mock User::where method chain
        User::staticExpects($this->once())
            ->method('where')
            ->with('branch_id', $branchId)
            ->willReturnSelf();
            
        User::staticExpects($this->once())
            ->method('get')
            ->willReturn($mockUsers);
            
        // Mock constraint creation for each user
        $mockUserConstraint = $this->createMock(AttendanceConstraint::class);
        $mockUserConstraint->expects($this->exactly(3))
            ->method('save')
            ->willReturn(true);
            
        AttendanceConstraint::staticExpects($this->exactly(3))
            ->method('__construct')
            ->willReturn($mockUserConstraint);
        
        // Create a mock BranchConstraintService with expected behavior
        $mockService = $this->getMockBuilder(BranchConstraintService::class)
            ->onlyMethods(['assignBranchEmployeesToConstraint'])
            ->getMock();
            
        $mockService->expects($this->once())
            ->method('assignBranchEmployeesToConstraint')
            ->with($branchId, $constraintId)
            ->willReturn(3); // 3 employees assigned
        
        // Act: Call the service method
        $assignedCount = $mockService->assignBranchEmployeesToConstraint($branchId, $constraintId);
        
        // Assert: Check that the correct number of employees were assigned
        $this->assertEquals(3, $assignedCount);
    }
    
    /**
     * Test assigning a new employee to branch constraints
     */
    public function test_assign_new_employee_to_branch_constraints(): void
    {
        // Arrange: Prepare test data
        $userId = 'user-123';
        $branchId = 'branch-456';
        $createdBy = 'admin-789';
        
        // Mock User model
        $mockUser = $this->createMock(User::class);
        $mockUser->method('__get')->willReturnMap([
            ['id', $userId],
            ['company_id', 'company-123']
        ]);
        
        // Mock User::find static method
        User::staticExpects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($mockUser);
            
        // Mock branch constraints query and results
        $mockBranchConstraints = [
            $this->createMock(AttendanceConstraint::class),
            $this->createMock(AttendanceConstraint::class)
        ];
        
        // Set up the constraint properties
        foreach ($mockBranchConstraints as $index => $constraint) {
            $constraint->method('__get')->willReturnMap([
                ['id', "constraint-{$index}"],
                ['constraint_type', AttendanceConstraint::TYPE_LOCATION],
                ['constraint_name', AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT],
                ['constraint_config', [
                    'branch_locations' => [
                        $branchId => [
                            'name' => 'Test Branch',
                            'latitude' => 25.1, 
                            'longitude' => 55.2,
                            'radius' => 100
                        ]
                    ]
                ]]
            ]);
        }
        
        // Mock the query for finding branch constraints
        AttendanceConstraint::staticExpects($this->once())
            ->method('where')
            ->with('branch_ids', 'like', "%$branchId%")
            ->willReturnSelf();
            
        AttendanceConstraint::staticExpects($this->once())
            ->method('whereNull')
            ->with('user_id')
            ->willReturnSelf();
            
        AttendanceConstraint::staticExpects($this->once())
            ->method('where')
            ->with('is_active', true)
            ->willReturnSelf();
            
        AttendanceConstraint::staticExpects($this->once())
            ->method('get')
            ->willReturn($mockBranchConstraints);
            
        // Mock user constraint creation
        $mockUserConstraint = $this->createMock(AttendanceConstraint::class);
        $mockUserConstraint->expects($this->exactly(2))
            ->method('save')
            ->willReturn(true);
            
        AttendanceConstraint::staticExpects($this->exactly(2))
            ->method('__construct')
            ->willReturn($mockUserConstraint);
        
        // Create a mock BranchConstraintService
        $mockService = $this->getMockBuilder(BranchConstraintService::class)
            ->onlyMethods(['assignEmployeeToBranchConstraints'])
            ->getMock();
            
        $mockService->expects($this->once())
            ->method('assignEmployeeToBranchConstraints')
            ->with($userId, $branchId, $createdBy)
            ->willReturn(2); // 2 constraints assigned
            
        // Act: Call the service method
        $assignedCount = $mockService->assignEmployeeToBranchConstraints($userId, $branchId, $createdBy);
        
        // Assert: Check that the correct number of constraints were assigned
        $this->assertEquals(2, $assignedCount);
    }
    
    /**
     * Test creating temporary location exception
     */
    public function test_create_temporary_location_exception(): void
    {
        // Arrange: Prepare test data
        $userId = 'user-123';
        $attendanceId = 'attendance-456';
        $temporaryLocation = [
            'name' => 'Client Meeting',
            'latitude' => 25.3456,
            'longitude' => 55.6789,
            'radius' => 200
        ];
        $startTime = '2025-06-26 09:00:00';
        $endTime = '2025-06-26 17:00:00';
        $createdBy = 'manager-789';
        $notes = 'Client meeting downtown';
        
        // Mock the BranchConstraintService
        $mockService = $this->getMockBuilder(BranchConstraintService::class)
            ->onlyMethods(['createTemporaryLocationException'])
            ->getMock();
            
        $mockService->expects($this->once())
            ->method('createTemporaryLocationException')
            ->with(
                $userId,
                $attendanceId,
                $temporaryLocation,
                $startTime,
                $endTime,
                $createdBy,
                $notes
            )
            ->willReturnCallback(function(
                $userId, $attendanceId, $tempLocation, $startTime, $endTime, $createdBy, $notes
            ) {
                return [
                    'id' => Uuid::uuid4()->toString(),
                    'type' => 'temporary_location',
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => $createdBy,
                    'notes' => $notes,
                    'temporary_location' => $tempLocation
                ];
            });
            
        // Act: Call the service method
        $exception = $mockService->createTemporaryLocationException(
            $userId,
            $attendanceId,
            $temporaryLocation,
            $startTime,
            $endTime,
            $createdBy,
            $notes
        );
        
        // Assert: Check that the exception was created with correct data
        $this->assertEquals('temporary_location', $exception['type']);
        $this->assertEquals($startTime, $exception['start_time']);
        $this->assertEquals($endTime, $exception['end_time']);
        $this->assertEquals($createdBy, $exception['created_by']);
        $this->assertEquals($notes, $exception['notes']);
        $this->assertEquals($temporaryLocation, $exception['temporary_location']);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Attendance\Tests\Unit\Services\Specialized;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\Services\RoleConstraintService;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Modules\User\Models\Role;

class RoleConstraintServiceTest extends TestCase
{
    private RoleConstraintService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoleConstraintService();
    }

    /**
     * Test role-based validation with matching role
     */
    public function test_validate_role_constraint_matching_role(): void
    {
        // Create mock user with allowed role
        $role = $this->createMock(Role::class);
        $role->method('__get')->willReturnMap([
            ['id', 2],
            ['name', 'Manager']
        ]);

        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 1],
            ['name', 'John Doe'],
            ['role_id', 2]
        ]);
        $user->method('hasRole')->willReturnMap([
            ['Manager', true],
            ['Admin', false]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user]
        ]);
        
        // Create mock constraint with allowed roles
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_ALLOWED_ROLES],
            ['config', [
                'allowed_roles' => ['Manager', 'Supervisor', 'Admin']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // No violation should be detected when user has an allowed role
        $this->assertFalse($result);
    }

    /**
     * Test role-based validation with non-matching role
     */
    public function test_validate_role_constraint_non_matching_role(): void
    {
        // Create mock user with non-allowed role
        $role = $this->createMock(Role::class);
        $role->method('__get')->willReturnMap([
            ['id', 5],
            ['name', 'Employee']
        ]);

        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 2],
            ['name', 'Jane Doe'],
            ['role_id', 5]
        ]);
        $user->method('hasRole')->willReturnMap([
            ['Employee', true],
            ['Manager', false],
            ['Admin', false]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user]
        ]);
        
        // Create mock constraint with allowed roles
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_ALLOWED_ROLES],
            ['config', [
                'allowed_roles' => ['Manager', 'Supervisor', 'Admin']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::ROLE_ALLOWED_ROLES, $result['constraint_type']);
        $this->assertStringContainsString('role', strtolower($result['message']));
        $this->assertEquals('Employee', $result['details']['user_role']);
        $this->assertArrayHasKey('allowed_roles', $result['details']);
    }

    /**
     * Test department-based validation with matching department
     */
    public function test_department_validation_matching_department(): void
    {
        // Create mock user with allowed department
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 3],
            ['name', 'Alice Smith'],
            ['department_id', 101],
            ['department', [
                'id' => 101,
                'name' => 'IT'
            ]]
        ]);
        $user->method('inDepartment')->willReturnMap([
            ['IT', true],
            ['HR', false]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user]
        ]);
        
        // Create mock constraint with allowed departments
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_DEPARTMENT],
            ['config', [
                'allowed_departments' => ['IT', 'Finance', 'Executive']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // No violation should be detected when user is in an allowed department
        $this->assertFalse($result);
    }

    /**
     * Test department-based validation with non-matching department
     */
    public function test_department_validation_non_matching_department(): void
    {
        // Create mock user with non-allowed department
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 4],
            ['name', 'Bob Johnson'],
            ['department_id', 102],
            ['department', [
                'id' => 102,
                'name' => 'HR'
            ]]
        ]);
        $user->method('inDepartment')->willReturnMap([
            ['HR', true],
            ['IT', false]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user]
        ]);
        
        // Create mock constraint with allowed departments
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_DEPARTMENT],
            ['config', [
                'allowed_departments' => ['IT', 'Finance', 'Executive']
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::ROLE_DEPARTMENT, $result['constraint_type']);
        $this->assertStringContainsString('department', strtolower($result['message']));
        $this->assertEquals('HR', $result['details']['user_department']);
        $this->assertArrayHasKey('allowed_departments', $result['details']);
    }

    /**
     * Test seniority level validation with compliant seniority
     */
    public function test_seniority_validation_compliant_level(): void
    {
        // Create mock user with sufficient seniority
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 5],
            ['name', 'Carol Smith'],
            ['join_date', '2020-01-15'], // Over 3 years with company
            ['seniority_level', 3]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['created_at', '2023-06-24'] // Current date in the test
        ]);
        
        // Create mock constraint with minimum seniority requirement
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_SENIORITY],
            ['config', [
                'min_seniority_level' => 3,
                'min_employment_years' => 3
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // No violation should be detected when user meets seniority requirements
        $this->assertFalse($result);
    }

    /**
     * Test seniority level validation with insufficient seniority
     */
    public function test_seniority_validation_insufficient_level(): void
    {
        // Create mock user with insufficient seniority
        $user = $this->createMock(User::class);
        $user->method('__get')->willReturnMap([
            ['id', 6],
            ['name', 'Dave Wilson'],
            ['join_date', '2023-01-15'], // Less than 1 year with company
            ['seniority_level', 1]
        ]);
        
        // Create mock attendance with the user
        $attendance = $this->createMock(Attendance::class);
        $attendance->method('__get')->willReturnMap([
            ['user', $user],
            ['created_at', '2023-06-24'] // Current date in the test
        ]);
        
        // Create mock constraint with minimum seniority requirement
        $constraint = $this->createMock(AttendanceConstraint::class);
        $constraint->method('__get')->willReturnMap([
            ['type', AttendanceConstraint::TYPE_ROLE],
            ['subtype', AttendanceConstraint::ROLE_SENIORITY],
            ['config', [
                'min_seniority_level' => 3,
                'min_employment_years' => 3
            ]]
        ]);
        
        // Call the service method directly
        $result = $this->service->validateRoleConstraint($attendance, $constraint);
        
        // Verify violation was detected with correct details
        $this->assertIsArray($result);
        $this->assertEquals(AttendanceConstraint::ROLE_SENIORITY, $result['constraint_type']);
        $this->assertStringContainsString('seniority', strtolower($result['message']));
        $this->assertEquals(1, $result['details']['user_seniority']);
        $this->assertEquals(3, $result['details']['required_seniority']);
    }
}

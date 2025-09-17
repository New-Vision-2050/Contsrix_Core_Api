<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Service to handle automatic constraint assignment when branches are created
 */
class BranchConstraintService
{
    /**
     * Create default location radius enforcement constraint for a new branch
     *
     * @param string $branchId The newly created branch ID
     * @param string $companyId The company ID
     * @param array $branchLocation Location data with latitude, longitude, and name
     * @param int $defaultRadius Default radius in meters (optional, defaults to 100)
     * @param string $createdBy User ID who created the branch
     * @return AttendanceConstraint
     */
    public function createDefaultBranchLocationConstraint(
        string $branchId, 
        string $companyId, 
        array $branchLocation, 
        int $defaultRadius = 100,
        string $createdBy
    ): AttendanceConstraint {
        // Create branch location constraint configuration
        $constraint = new AttendanceConstraint();
        $constraint->id = (string) Uuid::uuid4();
        $constraint->company_id = $companyId;
        $constraint->constraint_type = AttendanceConstraint::TYPE_LOCATION;
        $constraint->constraint_name = AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT;
        $constraint->branch_ids = [$branchId];
        
        // Set up branch locations configuration
        $branchLocations = [
            $branchId => [
                'name' => $branchLocation['name'] ?? 'Office Location',
                'latitude' => $branchLocation['latitude'],
                'longitude' => $branchLocation['longitude'],
                'radius' => $defaultRadius,
            ]
        ];
        
        // Set up constraint configuration
        $constraint->constraint_config = [
            'branch_locations' => $branchLocations,
            'enforcement' => [
                'out_of_radius_time_threshold' => 15, // Minutes allowed outside before enforcement
                'mark_absent_if_violated' => false,
                'end_shift_if_violated' => false,
                'allow_temporary_exceptions' => true
            ],
            'violation_severity' => 'medium'
        ];
        
        $constraint->is_active = true;
        $constraint->inherit_from_parent = false;
        $constraint->priority = 10; // Default priority
        $constraint->created_by = $createdBy;
        $constraint->save();
        
        return $constraint;
    }
    
    /**
     * Assign all employees of a branch to the branch's constraints
     *
     * @param string $branchId The branch ID
     * @param string $constraintId The constraint ID to assign
     * @return int Number of employees assigned
     */
    public function assignBranchEmployeesToConstraint(string $branchId, string $constraintId): int
    {
        // Get all users belonging to this branch
        $branchEmployees = User::where('branch_id', $branchId)->get();
        $count = 0;
        
        foreach ($branchEmployees as $employee) {
            // Create a user-specific override of the constraint
            $userConstraint = new AttendanceConstraint();
            $userConstraint->id = (string) Uuid::uuid4();
            $userConstraint->company_id = $employee->company_id;
            $userConstraint->user_id = $employee->id;
            $userConstraint->constraint_type = AttendanceConstraint::TYPE_LOCATION;
            $userConstraint->constraint_name = AttendanceConstraint::LOCATION_RADIUS_ENFORCEMENT;
            $userConstraint->branch_ids = [$branchId];
            
            // Copy configuration from branch constraint
            $branchConstraint = AttendanceConstraint::find($constraintId);
            if ($branchConstraint) {
                $userConstraint->constraint_config = $branchConstraint->constraint_config;
            }
            
            $userConstraint->is_active = true;
            $userConstraint->inherit_from_parent = true; // Inherit from branch constraint
            $userConstraint->parent_constraint_id = $constraintId;
            $userConstraint->priority = 20; // Higher priority than branch default
            $userConstraint->created_by = $branchConstraint->created_by ?? null;
            $userConstraint->save();
            
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Assign a new employee to all relevant branch constraints
     *
     * @param string $userId User ID of new employee
     * @param string $branchId Branch ID the employee belongs to
     * @param string $createdBy User ID who created the employee record
     * @return int Number of constraints assigned
     */
    public function assignEmployeeToBranchConstraints(string $userId, string $branchId, string $createdBy): int
    {
        // Get all branch-level constraints
        $branchConstraints = AttendanceConstraint::where('branch_ids', 'like', "%$branchId%")
            ->whereNull('user_id')
            ->where('is_active', true)
            ->get();
        
        $count = 0;
        $user = User::find($userId);
        
        if (!$user) {
            return 0;
        }
        
        foreach ($branchConstraints as $branchConstraint) {
            // Create a user-specific override of the constraint
            $userConstraint = new AttendanceConstraint();
            $userConstraint->id = (string) Uuid::uuid4();
            $userConstraint->company_id = $user->company_id;
            $userConstraint->user_id = $userId;
            $userConstraint->constraint_type = $branchConstraint->constraint_type;
            $userConstraint->constraint_name = $branchConstraint->constraint_name;
            $userConstraint->branch_ids = [$branchId];
            $userConstraint->constraint_config = $branchConstraint->constraint_config;
            $userConstraint->is_active = true;
            $userConstraint->inherit_from_parent = true; // Inherit from branch constraint
            $userConstraint->parent_constraint_id = $branchConstraint->id;
            $userConstraint->priority = 20; // Higher priority than branch default
            $userConstraint->created_by = $createdBy;
            $userConstraint->save();
            
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Handle temporary location exception creation
     *
     * @param string $userId User ID
     * @param string $attendanceId Attendance ID
     * @param array $temporaryLocation Temporary location data
     * @param string $startTime Start time of exception
     * @param string $endTime End time of exception
     * @param string $createdBy User ID who created the exception
     * @param string|null $notes Optional notes
     * @return array The created exception details
     */
    public function createTemporaryLocationException(
        string $userId,
        string $attendanceId,
        array $temporaryLocation,
        string $startTime,
        string $endTime,
        string $createdBy,
        ?string $notes = null
    ): array {
        $exception = [
            'id' => (string) Uuid::uuid4(),
            'type' => 'temporary_location',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'created_at' => Carbon::now()->toDateTimeString(),
            'created_by' => $createdBy,
            'notes' => $notes,
            'temporary_location' => [
                'name' => $temporaryLocation['name'] ?? 'Temporary Location',
                'latitude' => $temporaryLocation['latitude'],
                'longitude' => $temporaryLocation['longitude'],
                'radius' => $temporaryLocation['radius'] ?? 150, // Default 150m radius
            ]
        ];
        
        // Update the attendance record with the new exception
        // In a real implementation, you would get the attendance record and update it
        // $attendance = Attendance::find($attendanceId);
        // $exceptions = $attendance->exceptions ?? [];
        // $exceptions[] = $exception;
        // $attendance->exceptions = $exceptions;
        // $attendance->save();
        
        return $exception;
    }
}

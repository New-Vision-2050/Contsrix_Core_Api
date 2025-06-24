<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\RoleConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Service for role-related attendance constraint validations.
 */
class RoleConstraintService extends BaseConstraintService implements RoleConstraintServiceInterface
{
    /**
     * Validate role constraints for attendance.
     * This is a dispatcher method that handles different types of role constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        
        switch ($subtype) {
            case AttendanceConstraint::ROLE_HIERARCHY:
                return $this->validateRoleHierarchy($attendance, $config);
                
            case AttendanceConstraint::ROLE_PERMISSION:
                return $this->validateRolePermission($attendance, $config);
                
            case AttendanceConstraint::ROLE_DEPARTMENT:
                return $this->validateRoleDepartment($attendance, $config);
                
            case AttendanceConstraint::ROLE_SENIORITY:
                return $this->validateRoleSeniority($attendance, $config);
                
            case AttendanceConstraint::ROLE_SHIFT_ASSIGNMENT:
                return $this->validateRoleShiftAssignment($attendance, $config);
                
            default:
                return false;
        }
    }

    /**
     * Validate role hierarchy constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleHierarchy(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $userRole = $user->role ?? null;
        
        if (!$userRole) {
            return [
                'constraint_type' => AttendanceConstraint::ROLE_HIERARCHY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'User role information is required but is missing.',
                'details' => [
                    'user_id' => $user->id,
                    'role_provided' => false
                ]
            ];
        }
        
        // Check minimum role level requirement
        if (isset($config['min_role_level'])) {
            $minRoleLevel = (int)$config['min_role_level'];
            $userRoleLevel = (int)($userRole->level ?? 0);
            
            if ($userRoleLevel < $minRoleLevel) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_HIERARCHY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User role level is below the minimum required level.',
                    'details' => [
                        'user_role' => $userRole->name ?? 'Unknown',
                        'user_role_level' => $userRoleLevel,
                        'min_required_level' => $minRoleLevel
                    ]
                ];
            }
        }
        
        // Check maximum role level restriction
        if (isset($config['max_role_level'])) {
            $maxRoleLevel = (int)$config['max_role_level'];
            $userRoleLevel = (int)($userRole->level ?? 0);
            
            if ($userRoleLevel > $maxRoleLevel) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_HIERARCHY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User role level exceeds the maximum allowed level.',
                    'details' => [
                        'user_role' => $userRole->name ?? 'Unknown',
                        'user_role_level' => $userRoleLevel,
                        'max_allowed_level' => $maxRoleLevel
                    ]
                ];
            }
        }
        
        // Check allowed roles
        if (isset($config['allowed_roles']) && is_array($config['allowed_roles'])) {
            $userRoleName = $userRole->name ?? '';
            
            if (!in_array($userRoleName, $config['allowed_roles'])) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_HIERARCHY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User role is not in the allowed roles list.',
                    'details' => [
                        'user_role' => $userRoleName,
                        'allowed_roles' => $config['allowed_roles']
                    ]
                ];
            }
        }
        
        // Check blocked roles
        if (isset($config['blocked_roles']) && is_array($config['blocked_roles'])) {
            $userRoleName = $userRole->name ?? '';
            
            if (in_array($userRoleName, $config['blocked_roles'])) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_HIERARCHY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User role is in the blocked roles list.',
                    'details' => [
                        'user_role' => $userRoleName,
                        'blocked_roles' => $config['blocked_roles']
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Validate role permission constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRolePermission(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        
        // Check required permissions
        if (isset($config['required_permissions']) && is_array($config['required_permissions'])) {
            $missingPermissions = [];
            
            foreach ($config['required_permissions'] as $permission) {
                // This would typically check against a permission system
                // For now, we'll assume there's a method to check permissions
                if (!$this->userHasPermission($user, $permission)) {
                    $missingPermissions[] = $permission;
                }
            }
            
            if (!empty($missingPermissions)) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_PERMISSION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User is missing required permissions.',
                    'details' => [
                        'user_id' => $user->id,
                        'missing_permissions' => $missingPermissions,
                        'required_permissions' => $config['required_permissions']
                    ]
                ];
            }
        }
        
        // Check forbidden permissions
        if (isset($config['forbidden_permissions']) && is_array($config['forbidden_permissions'])) {
            $forbiddenPermissions = [];
            
            foreach ($config['forbidden_permissions'] as $permission) {
                if ($this->userHasPermission($user, $permission)) {
                    $forbiddenPermissions[] = $permission;
                }
            }
            
            if (!empty($forbiddenPermissions)) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_PERMISSION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User has forbidden permissions.',
                    'details' => [
                        'user_id' => $user->id,
                        'forbidden_permissions_found' => $forbiddenPermissions,
                        'forbidden_permissions' => $config['forbidden_permissions']
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Validate role department constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleDepartment(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $userDepartment = $user->department ?? null;
        
        if (!$userDepartment) {
            return [
                'constraint_type' => AttendanceConstraint::ROLE_DEPARTMENT,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'User department information is required but is missing.',
                'details' => [
                    'user_id' => $user->id,
                    'department_provided' => false
                ]
            ];
        }
        
        // Check allowed departments
        if (isset($config['allowed_departments']) && is_array($config['allowed_departments'])) {
            $userDepartmentName = $userDepartment->name ?? '';
            
            if (!in_array($userDepartmentName, $config['allowed_departments'])) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_DEPARTMENT,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User department is not in the allowed departments list.',
                    'details' => [
                        'user_department' => $userDepartmentName,
                        'allowed_departments' => $config['allowed_departments']
                    ]
                ];
            }
        }
        
        // Check blocked departments
        if (isset($config['blocked_departments']) && is_array($config['blocked_departments'])) {
            $userDepartmentName = $userDepartment->name ?? '';
            
            if (in_array($userDepartmentName, $config['blocked_departments'])) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_DEPARTMENT,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User department is in the blocked departments list.',
                    'details' => [
                        'user_department' => $userDepartmentName,
                        'blocked_departments' => $config['blocked_departments']
                    ]
                ];
            }
        }
        
        // Check department-specific time restrictions
        if (isset($config['department_time_restrictions']) && is_array($config['department_time_restrictions'])) {
            $userDepartmentName = $userDepartment->name ?? '';
            
            if (isset($config['department_time_restrictions'][$userDepartmentName])) {
                $timeRestriction = $config['department_time_restrictions'][$userDepartmentName];
                $clockInTime = Carbon::parse($attendance->clock_in_time)->format('H:i');
                
                $startTime = $timeRestriction['start_time'] ?? '00:00';
                $endTime = $timeRestriction['end_time'] ?? '23:59';
                
                if (!$this->isTimeWithinRange($clockInTime, $startTime, $endTime)) {
                    return [
                        'constraint_type' => AttendanceConstraint::ROLE_DEPARTMENT,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'Clock-in time is outside allowed hours for this department.',
                        'details' => [
                            'user_department' => $userDepartmentName,
                            'clock_in_time' => $clockInTime,
                            'allowed_hours' => $timeRestriction
                        ]
                    ];
                }
            }
        }
        
        return false;
    }

    /**
     * Validate role seniority constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleSeniority(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        
        // Check minimum years of experience
        if (isset($config['min_years_experience'])) {
            $minYears = (float)$config['min_years_experience'];
            $userExperience = (float)($user->years_experience ?? 0);
            
            if ($userExperience < $minYears) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_SENIORITY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User does not meet minimum years of experience requirement.',
                    'details' => [
                        'user_experience' => $userExperience,
                        'min_required_experience' => $minYears
                    ]
                ];
            }
        }
        
        // Check minimum tenure with company
        if (isset($config['min_company_tenure_months'])) {
            $minTenureMonths = (int)$config['min_company_tenure_months'];
            $hireDate = $user->hire_date ?? $user->created_at ?? null;
            
            if ($hireDate) {
                $tenureMonths = Carbon::parse($hireDate)->diffInMonths(now());
                
                if ($tenureMonths < $minTenureMonths) {
                    return [
                        'constraint_type' => AttendanceConstraint::ROLE_SENIORITY,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => 'User does not meet minimum company tenure requirement.',
                        'details' => [
                            'user_tenure_months' => $tenureMonths,
                            'min_required_tenure_months' => $minTenureMonths,
                            'hire_date' => $hireDate
                        ]
                    ];
                }
            }
        }
        
        // Check seniority level
        if (isset($config['min_seniority_level'])) {
            $minSeniorityLevel = (int)$config['min_seniority_level'];
            $userSeniorityLevel = (int)($user->seniority_level ?? 0);
            
            if ($userSeniorityLevel < $minSeniorityLevel) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_SENIORITY,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'User seniority level is below the minimum required level.',
                    'details' => [
                        'user_seniority_level' => $userSeniorityLevel,
                        'min_required_seniority_level' => $minSeniorityLevel
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Validate role shift assignment constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleShiftAssignment(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $clockInTime = Carbon::parse($attendance->clock_in_time);
        
        // Get user's assigned shift
        $assignedShift = $user->assigned_shift ?? null;
        
        if (!$assignedShift) {
            return [
                'constraint_type' => AttendanceConstraint::ROLE_SHIFT_ASSIGNMENT,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'User shift assignment information is required but is missing.',
                'details' => [
                    'user_id' => $user->id,
                    'shift_assigned' => false
                ]
            ];
        }
        
        // Check if user is clocking in during their assigned shift
        $shiftStartTime = $assignedShift->start_time ?? null;
        $shiftEndTime = $assignedShift->end_time ?? null;
        
        if ($shiftStartTime && $shiftEndTime) {
            $clockInTimeFormatted = $clockInTime->format('H:i');
            
            if (!$this->isTimeWithinRange($clockInTimeFormatted, $shiftStartTime, $shiftEndTime)) {
                return [
                    'constraint_type' => AttendanceConstraint::ROLE_SHIFT_ASSIGNMENT,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Clock-in time is outside assigned shift hours.',
                    'details' => [
                        'clock_in_time' => $clockInTimeFormatted,
                        'assigned_shift' => [
                            'start_time' => $shiftStartTime,
                            'end_time' => $shiftEndTime,
                            'name' => $assignedShift->name ?? 'Unknown'
                        ]
                    ]
                ];
            }
        }
        
        // Check day-specific shift assignments
        if (isset($config['check_daily_assignments']) && $config['check_daily_assignments']) {
            $dayOfWeek = $clockInTime->format('l'); // Full day name (e.g., 'Monday')
            $userDailyShifts = $user->daily_shift_assignments ?? [];
            
            if (isset($userDailyShifts[$dayOfWeek])) {
                $dailyShift = $userDailyShifts[$dayOfWeek];
                
                if (!$dailyShift['enabled'] ?? true) {
                    return [
                        'constraint_type' => AttendanceConstraint::ROLE_SHIFT_ASSIGNMENT,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => "User is not scheduled to work on {$dayOfWeek}.",
                        'details' => [
                            'day_of_week' => $dayOfWeek,
                            'shift_enabled' => false
                        ]
                    ];
                }
                
                $dailyStartTime = $dailyShift['start_time'] ?? null;
                $dailyEndTime = $dailyShift['end_time'] ?? null;
                
                if ($dailyStartTime && $dailyEndTime) {
                    $clockInTimeFormatted = $clockInTime->format('H:i');
                    
                    if (!$this->isTimeWithinRange($clockInTimeFormatted, $dailyStartTime, $dailyEndTime)) {
                        return [
                            'constraint_type' => AttendanceConstraint::ROLE_SHIFT_ASSIGNMENT,
                            'severity' => $this->getSeverityFromConfig($config),
                            'message' => "Clock-in time is outside assigned shift hours for {$dayOfWeek}.",
                            'details' => [
                                'day_of_week' => $dayOfWeek,
                                'clock_in_time' => $clockInTimeFormatted,
                                'daily_shift' => [
                                    'start_time' => $dailyStartTime,
                                    'end_time' => $dailyEndTime
                                ]
                            ]
                        ];
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Check if a user has a specific permission.
     * This is a placeholder method that would typically integrate with a permission system.
     * 
     * @param \Modules\User\Models\User $user The user to check
     * @param string $permission The permission to check for
     * @return bool True if user has permission, false otherwise
     */
    private function userHasPermission($user, string $permission): bool
    {
        // This would typically check against a permission system
        // For now, we'll assume there's a permissions relationship or method
        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission($permission);
        }
        
        // Fallback: check if user has permissions array
        $userPermissions = $user->permissions ?? [];
        return in_array($permission, $userPermissions);
    }
    
    /**
     * Validate department rules constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDepartmentRules(Attendance $attendance, array $config): bool|array
    {
        // Delegate to existing implementation
        return $this->validateRoleDepartment($attendance, $config);
    }
    
    /**
     * Validate level restrictions constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLevelRestrictions(Attendance $attendance, array $config): bool|array
    {
        // Delegate to existing implementation
        return $this->validateRoleHierarchy($attendance, $config);
    }
    
    /**
     * Validate probationary rules constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateProbationaryRules(Attendance $attendance, array $config): bool|array
    {
        // Delegate to existing implementation for seniority rules (which includes probation)
        return $this->validateRoleSeniority($attendance, $config);
    }
    
    /**
     * Validate contract constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateContractConstraints(Attendance $attendance, array $config): bool|array
    {
        // Also maps to seniority-related validation which would include contracts
        return $this->validateRoleSeniority($attendance, $config);
    }
    
    /**
     * Validate supervisor override constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSupervisorOverride(Attendance $attendance, array $config): bool|array
    {
        // Maps to permission validation which includes supervisor overrides
        return $this->validateRolePermission($attendance, $config);
    }
}

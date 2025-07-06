<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for role-related attendance constraint validations.
 */
interface RoleConstraintServiceInterface
{
    /**
     * Validate role constraints for attendance.
     * This is a dispatcher method that handles different types of role constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRoleConstraint(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate department rules constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDepartmentRules(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate level restrictions constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLevelRestrictions(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate probationary rules constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateProbationaryRules(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate contract constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateContractConstraints(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate supervisor override constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSupervisorOverride(Attendance $attendance, array $config): bool|array;
}

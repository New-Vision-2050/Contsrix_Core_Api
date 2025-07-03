<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for time-related attendance constraint validations.
 */
interface TimeConstraintServiceInterface
{
    /**
     * Validate time constraints for attendance.
     * This is a dispatcher method that handles different types of time constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateTimeConstraint(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate shift enforcement constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateShiftEnforcement(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate early prevention constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateEarlyPrevention(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate late restriction constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLateRestriction(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate break limits constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBreakLimits(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate overtime approval constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOvertimeApproval(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate multiple periods constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateMultiplePeriods(Attendance $attendance, array $config): bool|array;
}

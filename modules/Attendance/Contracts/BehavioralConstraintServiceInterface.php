<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for behavioral attendance constraint validations.
 */
interface BehavioralConstraintServiceInterface
{
    /**
     * Validate behavioral constraints for attendance.
     * This is a dispatcher method that handles different types of behavioral constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBehavioralConstraint(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate consecutive limit constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateConsecutiveLimit(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate weekly hours constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateWeeklyHours(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate rest periods constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRestPeriods(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate holiday work constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateHolidayWork(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate pattern monitoring constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validatePatternMonitoring(Attendance $attendance, array $config): bool|array;
}

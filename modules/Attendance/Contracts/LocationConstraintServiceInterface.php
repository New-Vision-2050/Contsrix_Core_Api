<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Interface for location-related attendance constraint validations.
 */
interface LocationConstraintServiceInterface
{
    /**
     * Validate location constraints for attendance.
     * This is a dispatcher method that handles different types of location constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLocationConstraint(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
    
    /**
     * Validate geofencing constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateGeofencing(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
    
    /**
     * Validate IP restriction constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateIpRestriction(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
    
    /**
     * Validate remote zones constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRemoteZones(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
    
    /**
     * Validate multi-location constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateMultiLocation(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
    
    /**
     * Validate office verification constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOfficeVerification(Attendance $attendance, AttendanceConstraint $constraint): bool|array;
}

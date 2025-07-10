<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for security-related attendance constraint validations.
 */
interface SecurityConstraintServiceInterface
{
    /**
     * Validate security constraints for attendance.
     * This is a dispatcher method that handles different types of security constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSecurityConstraint(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate two-factor authentication constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateTwoFactorAuth(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate biometric authentication constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBiometricAuth(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate audit trail constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAuditTrail(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate fraud detection constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateFraudDetection(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate data encryption constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDataEncryption(Attendance $attendance, array $config): bool|array;
}

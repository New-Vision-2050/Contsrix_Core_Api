<?php

namespace Modules\Attendance\Contracts;

use Modules\Attendance\Models\Attendance;

/**
 * Interface for compliance-related attendance constraint validations.
 */
interface ComplianceConstraintServiceInterface
{
    /**
     * Validate compliance constraints for attendance.
     * This is a dispatcher method that handles different types of compliance constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateComplianceConstraint(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate labor law compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLaborLaw(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate union agreement compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateUnionAgreement(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate industry rules compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateIndustryRules(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate government reporting compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateGovernmentReporting(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate documentation compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDocumentation(Attendance $attendance, array $config): bool|array;
    
    /**
     * Validate office verification compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOfficeVerification(Attendance $attendance, array $config): bool|array;
}

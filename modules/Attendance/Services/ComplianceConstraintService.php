<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\ComplianceConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\User\Repositories\UserRepository;
use Modules\Setting\Repositories\HolidayRepository;

/**
 * Service for compliance-related attendance constraint validations.
 */
class ComplianceConstraintService extends BaseConstraintService implements ComplianceConstraintServiceInterface
{
    protected $userRepository;
    protected $holidayRepository;

    public function __construct(UserRepository $userRepository, HolidayRepository $holidayRepository)
    {
        $this->userRepository = $userRepository;
        $this->holidayRepository = $holidayRepository;
    }

    /**
     * Validate compliance constraints for attendance.
     * This is a dispatcher method that handles different types of compliance constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateComplianceConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        
        switch ($subtype) {
            case AttendanceConstraint::COMPLIANCE_LABOR_LAW:
                return $this->validateLaborLaw($attendance, $config);
                
            case AttendanceConstraint::COMPLIANCE_UNION_AGREEMENT:
                return $this->validateUnionAgreement($attendance, $config);
                
            case AttendanceConstraint::COMPLIANCE_INDUSTRY_RULES:
                return $this->validateIndustryRules($attendance, $config);
                
            case AttendanceConstraint::COMPLIANCE_GOVERNMENT_REPORTING:
                return $this->validateGovernmentReporting($attendance, $config);
                
            case AttendanceConstraint::COMPLIANCE_DOCUMENTATION:
                return $this->validateDocumentation($attendance, $config);
                
            case AttendanceConstraint::COMPLIANCE_OFFICE_VERIFICATION:
                return $this->validateOfficeVerification($attendance, $config);
                
            default:
                return false;
        }
    }

    /**
     * Validate labor law compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLaborLaw(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check maximum daily work hours
        if (isset($config['max_daily_hours'])) {
            $maxDailyHours = (float)$config['max_daily_hours'];
            $actualHours = $attendance->total_hours ?? 0;
            
            if ($actualHours > $maxDailyHours) {
                $violations[] = [
                    'type' => 'max_daily_hours_exceeded',
                    'message' => "Daily work hours ({$actualHours}) exceed legal limit ({$maxDailyHours})",
                    'actual_hours' => $actualHours,
                    'max_hours' => $maxDailyHours
                ];
            }
        }
        
        // Check required breaks
        if (isset($config['required_breaks']) && is_array($config['required_breaks'])) {
            foreach ($config['required_breaks'] as $requiredBreak) {
                $minWorkHours = $requiredBreak['min_work_hours'] ?? 0;
                $minBreakMinutes = $requiredBreak['min_break_minutes'] ?? 0;
                
                if (($attendance->total_hours ?? 0) >= $minWorkHours) {
                    $breaks = $attendance->breaks ?? [];
                    $totalBreakMinutes = 0;
                    
                    foreach ($breaks as $break) {
                        if (isset($break['start_time']) && isset($break['end_time'])) {
                            $startTime = Carbon::parse($break['start_time']);
                            $endTime = Carbon::parse($break['end_time']);
                            $totalBreakMinutes += $startTime->diffInMinutes($endTime);
                        }
                    }
                    
                    if ($totalBreakMinutes < $minBreakMinutes) {
                        $violations[] = [
                            'type' => 'insufficient_break_time',
                            'message' => "Insufficient break time ({$totalBreakMinutes} min) for work duration. Required: {$minBreakMinutes} min",
                            'actual_break_minutes' => $totalBreakMinutes,
                            'required_break_minutes' => $minBreakMinutes
                        ];
                    }
                }
            }
        }
        
        // Check region-specific rules
        if (isset($config['region_rules']) && is_array($config['region_rules'])) {
            $userRegion = $attendance->user->region ?? null;
            
            if ($userRegion && isset($config['region_rules'][$userRegion])) {
                $regionRules = $config['region_rules'][$userRegion];
                
                // Check region-specific maximum hours
                if (isset($regionRules['max_daily_hours'])) {
                    $regionMaxHours = (float)$regionRules['max_daily_hours'];
                    $actualHours = $attendance->total_hours ?? 0;
                    
                    if ($actualHours > $regionMaxHours) {
                        $violations[] = [
                            'type' => 'region_max_hours_exceeded',
                            'message' => "Daily work hours ({$actualHours}) exceed regional limit for {$userRegion} ({$regionMaxHours})",
                            'region' => $userRegion,
                            'actual_hours' => $actualHours,
                            'region_max_hours' => $regionMaxHours
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_LABOR_LAW,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Labor law compliance violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate union agreement compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateUnionAgreement(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check union-specific clock-in times
        if (isset($config['union_clock_in_rules']) && is_array($config['union_clock_in_rules'])) {
            $userUnion = $attendance->user->union_id ?? null;
            
            if ($userUnion && isset($config['union_clock_in_rules'][$userUnion])) {
                $unionRules = $config['union_clock_in_rules'][$userUnion];
                
                if (isset($unionRules['earliest_clock_in']) && $attendance->clock_in_time) {
                    $earliestTime = $unionRules['earliest_clock_in'];
                    $clockInTime = Carbon::parse($attendance->clock_in_time)->format('H:i');
                    
                    if ($clockInTime < $earliestTime) {
                        $violations[] = [
                            'type' => 'union_early_clock_in',
                            'message' => "Clock-in time ({$clockInTime}) violates union agreement (earliest: {$earliestTime})",
                            'union_id' => $userUnion,
                            'clock_in_time' => $clockInTime,
                            'earliest_allowed' => $earliestTime
                        ];
                    }
                }
            }
        }
        
        // Check union pay rate compliance
        if (isset($config['union_pay_rates']) && is_array($config['union_pay_rates'])) {
            $userUnion = $attendance->user->union_id ?? null;
            
            if ($userUnion && isset($config['union_pay_rates'][$userUnion])) {
                $unionPayRate = $config['union_pay_rates'][$userUnion];
                $actualPayRate = $attendance->hourly_rate ?? 0;
                
                if ($actualPayRate < $unionPayRate) {
                    $violations[] = [
                        'type' => 'union_pay_rate_violation',
                        'message' => "Pay rate ({$actualPayRate}) below union minimum ({$unionPayRate})",
                        'union_id' => $userUnion,
                        'actual_rate' => $actualPayRate,
                        'union_minimum' => $unionPayRate
                    ];
                }
            }
        }
        
        // Check union overtime rules
        if (isset($config['union_overtime_rules']) && ($attendance->overtime_minutes ?? 0) > 0) {
            $userUnion = $attendance->user->union_id ?? null;
            
            if ($userUnion && isset($config['union_overtime_rules'][$userUnion])) {
                $overtimeRules = $config['union_overtime_rules'][$userUnion];
                
                if (isset($overtimeRules['requires_approval']) && $overtimeRules['requires_approval']) {
                    $isApproved = $attendance->overtime_approved ?? false;
                    
                    if (!$isApproved) {
                        $violations[] = [
                            'type' => 'union_overtime_approval_required',
                            'message' => "Union agreement requires overtime approval",
                            'union_id' => $userUnion,
                            'overtime_minutes' => $attendance->overtime_minutes
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_UNION_AGREEMENT,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Union agreement compliance violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate industry rules compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateIndustryRules(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check industry-specific certifications
        if (isset($config['required_certifications']) && is_array($config['required_certifications'])) {
            $userCertifications = $attendance->user->certifications ?? [];
            
            foreach ($config['required_certifications'] as $requiredCert) {
                $hasCertification = false;
                
                foreach ($userCertifications as $userCert) {
                    if ($userCert['type'] === $requiredCert['type']) {
                        // Check if certification is still valid
                        if (isset($userCert['expiry_date'])) {
                            $expiryDate = Carbon::parse($userCert['expiry_date']);
                            if ($expiryDate->isFuture()) {
                                $hasCertification = true;
                                break;
                            }
                        } else {
                            $hasCertification = true;
                            break;
                        }
                    }
                }
                
                if (!$hasCertification) {
                    $violations[] = [
                        'type' => 'missing_certification',
                        'message' => "Required certification missing or expired: {$requiredCert['type']}",
                        'certification_type' => $requiredCert['type']
                    ];
                }
            }
        }
        
        // Check industry-specific rest periods
        if (isset($config['mandatory_rest_periods']) && is_array($config['mandatory_rest_periods'])) {
            foreach ($config['mandatory_rest_periods'] as $restPeriod) {
                $minWorkHours = $restPeriod['min_work_hours'] ?? 0;
                $minRestHours = $restPeriod['min_rest_hours'] ?? 0;
                
                if (($attendance->total_hours ?? 0) >= $minWorkHours) {
                    // Check if there's sufficient rest before next shift
                    // This would typically require checking against the next attendance record
                    // For now, we'll check if the rest period field is populated
                    $restHours = $attendance->rest_hours_before_next_shift ?? 0;
                    
                    if ($restHours < $minRestHours) {
                        $violations[] = [
                            'type' => 'insufficient_rest_period',
                            'message' => "Insufficient rest period ({$restHours}h) for work duration. Required: {$minRestHours}h",
                            'actual_rest_hours' => $restHours,
                            'required_rest_hours' => $minRestHours
                        ];
                    }
                }
            }
        }
        
        // Check industry-specific equipment requirements
        if (isset($config['required_equipment']) && is_array($config['required_equipment'])) {
            $attendanceEquipment = $attendance->equipment_used ?? [];
            
            foreach ($config['required_equipment'] as $requiredEquipment) {
                $hasEquipment = in_array($requiredEquipment, $attendanceEquipment);
                
                if (!$hasEquipment) {
                    $violations[] = [
                        'type' => 'missing_required_equipment',
                        'message' => "Required equipment not recorded: {$requiredEquipment}",
                        'equipment' => $requiredEquipment
                    ];
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_INDUSTRY_RULES,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Industry rules compliance violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate government reporting compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateGovernmentReporting(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check required fields for government reporting
        if (isset($config['required_fields']) && is_array($config['required_fields'])) {
            foreach ($config['required_fields'] as $field) {
                $value = $attendance->{$field} ?? null;
                
                if (empty($value)) {
                    $violations[] = [
                        'type' => 'missing_required_field',
                        'message' => "Required field for government reporting is missing: {$field}",
                        'field' => $field
                    ];
                }
            }
        }
        
        // Check tax ID requirements
        if (isset($config['tax_id_required']) && $config['tax_id_required']) {
            $taxId = $attendance->user->tax_id ?? null;
            
            if (empty($taxId)) {
                $violations[] = [
                    'type' => 'missing_tax_id',
                    'message' => "Tax ID is required for government reporting but is missing",
                    'user_id' => $attendance->user_id
                ];
            }
        }
        
        // Check social security number requirements
        if (isset($config['ssn_required']) && $config['ssn_required']) {
            $ssn = $attendance->user->social_security_number ?? null;
            
            if (empty($ssn)) {
                $violations[] = [
                    'type' => 'missing_ssn',
                    'message' => "Social Security Number is required for government reporting but is missing",
                    'user_id' => $attendance->user_id
                ];
            }
        }
        
        // Check work authorization status
        if (isset($config['work_authorization_required']) && $config['work_authorization_required']) {
            $workAuth = $attendance->user->work_authorization_status ?? null;
            
            if (empty($workAuth) || $workAuth !== 'authorized') {
                $violations[] = [
                    'type' => 'invalid_work_authorization',
                    'message' => "Valid work authorization is required for government reporting",
                    'user_id' => $attendance->user_id,
                    'current_status' => $workAuth
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_GOVERNMENT_REPORTING,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Government reporting compliance violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate documentation compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDocumentation(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check required documentation types
        if (isset($config['required_document_types']) && is_array($config['required_document_types'])) {
            $attachedDocuments = $attendance->documents ?? [];
            
            foreach ($config['required_document_types'] as $requiredType) {
                $hasDocumentType = false;
                
                foreach ($attachedDocuments as $document) {
                    if (($document['type'] ?? '') === $requiredType) {
                        $hasDocumentType = true;
                        break;
                    }
                }
                
                if (!$hasDocumentType) {
                    $violations[] = [
                        'type' => 'missing_document_type',
                        'message' => "Required document type is missing: {$requiredType}",
                        'document_type' => $requiredType
                    ];
                }
            }
        }
        
        // Check required notes/comments
        if (isset($config['notes_required']) && $config['notes_required']) {
            $notes = $attendance->notes ?? '';
            
            if (empty(trim($notes))) {
                $violations[] = [
                    'type' => 'missing_notes',
                    'message' => "Notes/comments are required but are missing"
                ];
            }
        }
        
        // Check supervisor approval documentation
        if (isset($config['supervisor_approval_required']) && $config['supervisor_approval_required']) {
            $supervisorApproval = $attendance->supervisor_approval ?? null;
            
            if (empty($supervisorApproval)) {
                $violations[] = [
                    'type' => 'missing_supervisor_approval',
                    'message' => "Supervisor approval documentation is required but is missing"
                ];
            } else {
                // Check if approval has required fields
                $requiredApprovalFields = $config['approval_required_fields'] ?? ['supervisor_id', 'approval_date'];
                
                foreach ($requiredApprovalFields as $field) {
                    if (empty($supervisorApproval[$field] ?? null)) {
                        $violations[] = [
                            'type' => 'incomplete_supervisor_approval',
                            'message' => "Supervisor approval is missing required field: {$field}",
                            'field' => $field
                        ];
                    }
                }
            }
        }
        
        // Check digital signature requirements
        if (isset($config['digital_signature_required']) && $config['digital_signature_required']) {
            $digitalSignature = $attendance->digital_signature ?? null;
            
            if (empty($digitalSignature)) {
                $violations[] = [
                    'type' => 'missing_digital_signature',
                    'message' => "Digital signature is required but is missing"
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_DOCUMENTATION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Documentation compliance violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate office verification compliance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOfficeVerification(Attendance $attendance, array $config): bool|array
    {
        // Check if office verification is required
        $verificationRequired = $config['verification_required'] ?? false;
        if (!$verificationRequired) {
            return false;
        }
        
        // Check exemption conditions
        if (isset($config['exemptions']) && is_array($config['exemptions'])) {
            // Check duration exemption
            if (isset($config['exemptions']['min_duration_hours'])) {
                $minDurationHours = (float)$config['exemptions']['min_duration_hours'];
                $actualHours = $attendance->total_hours ?? 0;
                
                if ($actualHours < $minDurationHours) {
                    return false; // Exempt due to short duration
                }
            }
            
            // Check role exemptions
            if (isset($config['exemptions']['roles']) && is_array($config['exemptions']['roles'])) {
                $userRole = $attendance->user->role ?? null;
                
                if ($userRole && in_array($userRole, $config['exemptions']['roles'])) {
                    return false; // Exempt due to role
                }
            }
        }
        
        // Check if verification exists
        $verification = $attendance->office_verification ?? null;
        
        if (empty($verification)) {
            return [
                'constraint_type' => AttendanceConstraint::COMPLIANCE_OFFICE_VERIFICATION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Office verification is required but is missing.',
                'details' => [
                    'verification_required' => true,
                    'verification_status' => 'missing'
                ]
            ];
        }
        
        // Check verification deadline
        if (isset($config['verification_deadline_hours']) && $attendance->clock_out_time) {
            $deadlineHours = (int)$config['verification_deadline_hours'];
            $clockOutTime = Carbon::parse($attendance->clock_out_time);
            $verificationTime = Carbon::parse($verification['verified_at'] ?? now());
            $deadline = $clockOutTime->addHours($deadlineHours);
            
            if ($verificationTime->gt($deadline)) {
                return [
                    'constraint_type' => AttendanceConstraint::COMPLIANCE_OFFICE_VERIFICATION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Office verification was completed after the deadline.',
                    'details' => [
                        'verification_time' => $verificationTime->toDateTimeString(),
                        'deadline' => $deadline->toDateTimeString(),
                        'hours_late' => $verificationTime->diffInHours($deadline)
                    ]
                ];
            }
        }
        
        // Check verification type requirements
        if (isset($config['required_verification_type'])) {
            $requiredType = $config['required_verification_type'];
            $actualType = $verification['type'] ?? 'standard';
            
            if ($actualType !== $requiredType) {
                return [
                    'constraint_type' => AttendanceConstraint::COMPLIANCE_OFFICE_VERIFICATION,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => "Incorrect verification type. Required: {$requiredType}, Actual: {$actualType}",
                    'details' => [
                        'required_type' => $requiredType,
                        'actual_type' => $actualType
                    ]
                ];
            }
        }
        
        return false;
    }
}

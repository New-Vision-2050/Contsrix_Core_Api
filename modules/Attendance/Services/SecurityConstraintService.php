<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\SecurityConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Service for security-related attendance constraint validations.
 */
class SecurityConstraintService extends BaseConstraintService implements SecurityConstraintServiceInterface
{
    /**
     * Validate security constraints for attendance.
     * This is a dispatcher method that handles different types of security constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSecurityConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        
        switch ($subtype) {
            case AttendanceConstraint::SECURITY_TWO_FACTOR:
                return $this->validateTwoFactorAuth($attendance, $config);
                
            case AttendanceConstraint::SECURITY_BIOMETRIC:
                return $this->validateBiometricAuth($attendance, $config);
                
            case AttendanceConstraint::SECURITY_AUDIT_TRAIL:
                return $this->validateAuditTrail($attendance, $config);
                
            case AttendanceConstraint::SECURITY_FRAUD_DETECTION:
                return $this->validateFraudDetection($attendance, $config);
                
            case AttendanceConstraint::SECURITY_DATA_ENCRYPTION:
                return $this->validateDataEncryption($attendance, $config);
                
            default:
                return false;
        }
    }

    /**
     * Validate two-factor authentication constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateTwoFactorAuth(Attendance $attendance, array $config): bool|array
    {
        // Check if two-factor authentication is required
        $twoFactorRequired = $config['two_factor_required'] ?? false;
        if (!$twoFactorRequired) {
            return false;
        }
        
        // Check if two-factor authentication was used
        $twoFactorUsed = $attendance->two_factor_auth_used ?? false;
        
        if (!$twoFactorUsed) {
            return [
                'constraint_type' => AttendanceConstraint::SECURITY_TWO_FACTOR,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Two-factor authentication is required but was not used.',
                'details' => [
                    'two_factor_required' => true,
                    'two_factor_used' => false
                ]
            ];
        }
        
        // Check two-factor method requirements
        if (isset($config['required_methods']) && is_array($config['required_methods'])) {
            $usedMethod = $attendance->two_factor_method ?? null;
            
            if (!$usedMethod || !in_array($usedMethod, $config['required_methods'])) {
                return [
                    'constraint_type' => AttendanceConstraint::SECURITY_TWO_FACTOR,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Invalid two-factor authentication method used.',
                    'details' => [
                        'required_methods' => $config['required_methods'],
                        'used_method' => $usedMethod
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Validate biometric authentication constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBiometricAuth(Attendance $attendance, array $config): bool|array
    {
        // Check if biometric authentication is required
        $biometricRequired = $config['biometric_required'] ?? false;
        if (!$biometricRequired) {
            return false;
        }
        
        // Check if biometric authentication was used
        $biometricUsed = $attendance->biometric_auth_used ?? false;
        
        if (!$biometricUsed) {
            return [
                'constraint_type' => AttendanceConstraint::SECURITY_BIOMETRIC,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Biometric authentication is required but was not used.',
                'details' => [
                    'biometric_required' => true,
                    'biometric_used' => false
                ]
            ];
        }
        
        // Check biometric type requirements
        if (isset($config['required_biometric_types']) && is_array($config['required_biometric_types'])) {
            $usedBiometricType = $attendance->biometric_type ?? null;
            
            if (!$usedBiometricType || !in_array($usedBiometricType, $config['required_biometric_types'])) {
                return [
                    'constraint_type' => AttendanceConstraint::SECURITY_BIOMETRIC,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Invalid biometric authentication type used.',
                    'details' => [
                        'required_types' => $config['required_biometric_types'],
                        'used_type' => $usedBiometricType
                    ]
                ];
            }
        }
        
        // Check biometric confidence score
        if (isset($config['min_confidence_score'])) {
            $minConfidence = (float)$config['min_confidence_score'];
            $actualConfidence = (float)($attendance->biometric_confidence_score ?? 0);
            
            if ($actualConfidence < $minConfidence) {
                return [
                    'constraint_type' => AttendanceConstraint::SECURITY_BIOMETRIC,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => 'Biometric authentication confidence score is too low.',
                    'details' => [
                        'min_confidence' => $minConfidence,
                        'actual_confidence' => $actualConfidence
                    ]
                ];
            }
        }
        
        return false;
    }

    /**
     * Validate audit trail constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAuditTrail(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check required audit trail fields
        if (isset($config['required_fields']) && is_array($config['required_fields'])) {
            foreach ($config['required_fields'] as $field) {
                $value = null;
                
                // Check in different possible locations for audit trail data
                if (isset($attendance->audit_trail[$field])) {
                    $value = $attendance->audit_trail[$field];
                } elseif (isset($attendance->{$field})) {
                    $value = $attendance->{$field};
                }
                
                if (empty($value)) {
                    $violations[] = [
                        'type' => 'missing_audit_field',
                        'message' => "Required audit trail field is missing: {$field}",
                        'field' => $field
                    ];
                }
            }
        }
        
        // Check IP address logging
        if (isset($config['ip_logging_required']) && $config['ip_logging_required']) {
            $ipAddress = $attendance->ip_address ?? $attendance->audit_trail['ip_address'] ?? null;
            
            if (empty($ipAddress)) {
                $violations[] = [
                    'type' => 'missing_ip_address',
                    'message' => 'IP address logging is required but is missing'
                ];
            }
        }
        
        // Check device information logging
        if (isset($config['device_info_required']) && $config['device_info_required']) {
            $deviceInfo = $attendance->device_info ?? $attendance->audit_trail['device_info'] ?? null;
            
            if (empty($deviceInfo)) {
                $violations[] = [
                    'type' => 'missing_device_info',
                    'message' => 'Device information logging is required but is missing'
                ];
            }
        }
        
        // Check location logging
        if (isset($config['location_logging_required']) && $config['location_logging_required']) {
            $location = $attendance->location ?? $attendance->audit_trail['location'] ?? null;
            
            if (empty($location)) {
                $violations[] = [
                    'type' => 'missing_location',
                    'message' => 'Location logging is required but is missing'
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::SECURITY_AUDIT_TRAIL,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Audit trail security violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate fraud detection constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateFraudDetection(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check for suspicious location patterns
        if (isset($config['location_fraud_detection']) && $config['location_fraud_detection']) {
            $currentLocation = $attendance->location ?? null;
            $previousLocation = $attendance->previous_location ?? null;
            
            if ($currentLocation && $previousLocation && isset($config['max_travel_speed_kmh'])) {
                $maxSpeedKmh = (float)$config['max_travel_speed_kmh'];
                
                // Calculate distance and time between locations
                if (isset($currentLocation['latitude'], $currentLocation['longitude'], 
                          $previousLocation['latitude'], $previousLocation['longitude'])) {
                    
                    $distance = $this->calculateDistance(
                        $previousLocation['latitude'], $previousLocation['longitude'],
                        $currentLocation['latitude'], $currentLocation['longitude']
                    );
                    
                    $timeDiffHours = $attendance->time_since_previous_location ?? 1;
                    $actualSpeedKmh = $distance / $timeDiffHours;
                    
                    if ($actualSpeedKmh > $maxSpeedKmh) {
                        $violations[] = [
                            'type' => 'impossible_travel_speed',
                            'message' => "Impossible travel speed detected: {$actualSpeedKmh} km/h (max: {$maxSpeedKmh} km/h)",
                            'actual_speed' => $actualSpeedKmh,
                            'max_speed' => $maxSpeedKmh,
                            'distance_km' => $distance,
                            'time_hours' => $timeDiffHours
                        ];
                    }
                }
            }
        }
        
        // Check for duplicate clock-ins
        if (isset($config['duplicate_detection']) && $config['duplicate_detection']) {
            $duplicateWindow = (int)($config['duplicate_window_minutes'] ?? 5);
            
            // This would typically require checking against other attendance records
            // For now, we'll check if there's a flag indicating potential duplicate
            $isDuplicate = $attendance->potential_duplicate ?? false;
            
            if ($isDuplicate) {
                $violations[] = [
                    'type' => 'potential_duplicate',
                    'message' => "Potential duplicate attendance record detected within {$duplicateWindow} minutes",
                    'duplicate_window' => $duplicateWindow
                ];
            }
        }
        
        // Check for unusual time patterns
        if (isset($config['time_pattern_detection']) && $config['time_pattern_detection']) {
            $clockInTime = Carbon::parse($attendance->clock_in_time);
            $hour = $clockInTime->hour;
            
            // Check for clock-ins at unusual hours
            $unusualHours = $config['unusual_hours'] ?? [0, 1, 2, 3, 4, 5, 22, 23];
            
            if (in_array($hour, $unusualHours)) {
                $violations[] = [
                    'type' => 'unusual_clock_in_time',
                    'message' => "Clock-in at unusual hour: {$hour}:00",
                    'clock_in_hour' => $hour,
                    'unusual_hours' => $unusualHours
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::SECURITY_FRAUD_DETECTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Potential fraud detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate data encryption constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateDataEncryption(Attendance $attendance, array $config): bool|array
    {
        $violations = [];
        
        // Check if sensitive data is encrypted
        if (isset($config['encrypted_fields_required']) && is_array($config['encrypted_fields_required'])) {
            foreach ($config['encrypted_fields_required'] as $field) {
                $isEncrypted = $attendance->{"is_{$field}_encrypted"} ?? false;
                
                if (!$isEncrypted) {
                    $violations[] = [
                        'type' => 'unencrypted_sensitive_data',
                        'message' => "Sensitive field is not encrypted: {$field}",
                        'field' => $field
                    ];
                }
            }
        }
        
        // Check encryption algorithm requirements
        if (isset($config['required_encryption_algorithm'])) {
            $requiredAlgorithm = $config['required_encryption_algorithm'];
            $usedAlgorithm = $attendance->encryption_algorithm ?? null;
            
            if ($usedAlgorithm !== $requiredAlgorithm) {
                $violations[] = [
                    'type' => 'incorrect_encryption_algorithm',
                    'message' => "Incorrect encryption algorithm used. Required: {$requiredAlgorithm}, Used: {$usedAlgorithm}",
                    'required_algorithm' => $requiredAlgorithm,
                    'used_algorithm' => $usedAlgorithm
                ];
            }
        }
        
        // Check encryption key strength
        if (isset($config['min_key_length'])) {
            $minKeyLength = (int)$config['min_key_length'];
            $actualKeyLength = (int)($attendance->encryption_key_length ?? 0);
            
            if ($actualKeyLength < $minKeyLength) {
                $violations[] = [
                    'type' => 'weak_encryption_key',
                    'message' => "Encryption key length is too weak. Required: {$minKeyLength} bits, Used: {$actualKeyLength} bits",
                    'min_key_length' => $minKeyLength,
                    'actual_key_length' => $actualKeyLength
                ];
            }
        }
        
        // Check data transmission encryption
        if (isset($config['transmission_encryption_required']) && $config['transmission_encryption_required']) {
            $transmissionEncrypted = $attendance->transmission_encrypted ?? false;
            
            if (!$transmissionEncrypted) {
                $violations[] = [
                    'type' => 'unencrypted_transmission',
                    'message' => 'Data transmission encryption is required but was not used'
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::SECURITY_DATA_ENCRYPTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Data encryption security violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Calculate distance between two geographic points using Haversine formula.
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}

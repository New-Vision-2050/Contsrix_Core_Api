<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceConstraintService
{
    /**
     * Validate attendance against all applicable constraints.
     */
    public function validateAttendance(Attendance $attendance, array $requestData = []): array
    {
        $violations = [];
        $user = $attendance->user;
        
        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($user);
        
        foreach ($constraints as $constraint) {
            $violation = $this->validateSingleConstraint($attendance, $constraint, $requestData);
            if ($violation) {
                $violations[] = $violation;
            }
        }
        
        return $violations;
    }

    /**
     * Get all constraints applicable to a user.
     */
    public function getApplicableConstraints(User $user): Collection
    {
        return AttendanceConstraint::where('company_id', $user->company_id)
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id') // Company-wide constraints
                      ->orWhere('user_id', $user->id); // User-specific constraints
            })
            ->active()
            ->byPriority()
            ->get()
            ->filter(function ($constraint) {
                return $constraint->isValidForDate();
            });
    }

    /**
     * Validate a single constraint against attendance.
     */
    protected function validateSingleConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData): ?array
    {
        switch ($constraint->constraint_type) {
            case AttendanceConstraint::TYPE_LOCATION:
                return $this->validateLocationConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_TIME:
                return $this->validateTimeConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_DEVICE:
                return $this->validateDeviceConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_ROLE:
                return $this->validateRoleConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_BEHAVIORAL:
                return $this->validateBehavioralConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_SECURITY:
                return $this->validateSecurityConstraint($attendance, $constraint, $requestData);
            
            case AttendanceConstraint::TYPE_COMPLIANCE:
                return $this->validateComplianceConstraint($attendance, $constraint, $requestData);
            
            default:
                return null;
        }
    }

    /**
     * Validate location-based constraints.
     */
    protected function validateLocationConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData): ?array
    {
        $config = $constraint->constraint_config;
        
        switch ($constraint->constraint_name) {
            case AttendanceConstraint::LOCATION_GEOFENCING:
                return $this->validateGeofencing($attendance, $config, $requestData);
            
            case AttendanceConstraint::LOCATION_IP_RESTRICTION:
                return $this->validateIpRestriction($attendance, $config, $requestData);
            
            case AttendanceConstraint::LOCATION_OFFICE_VERIFICATION:
                return $this->validateOfficeVerification($attendance, $config, $requestData);
            
            default:
                return null;
        }
    }

    /**
     * Validate geofencing constraint.
     */
    protected function validateGeofencing(Attendance $attendance, array $config, array $requestData): ?array
    {
        if (!isset($requestData['latitude']) || !isset($requestData['longitude'])) {
            return [
                'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
                'violation_type' => AttendanceConstraintViolation::TYPE_LOCATION_VIOLATION,
                'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
                'message' => 'Location data required for geofencing validation',
                'details' => ['missing_location_data' => true]
            ];
        }

        $userLat = (float) $requestData['latitude'];
        $userLng = (float) $requestData['longitude'];
        
        foreach ($config['allowed_zones'] ?? [] as $zone) {
            $distance = $this->calculateDistance(
                $userLat, $userLng,
                $zone['latitude'], $zone['longitude']
            );
            
            if ($distance <= $zone['radius']) {
                return null; // Within allowed zone
            }
        }
        
        return [
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'violation_type' => AttendanceConstraintViolation::TYPE_LOCATION_VIOLATION,
            'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
            'message' => 'User location outside allowed geofenced areas',
            'details' => [
                'user_location' => ['lat' => $userLat, 'lng' => $userLng],
                'allowed_zones' => $config['allowed_zones']
            ]
        ];
    }

    /**
     * Validate IP restriction constraint.
     */
    protected function validateIpRestriction(Attendance $attendance, array $config, array $requestData): ?array
    {
        $userIp = $requestData['ip_address'] ?? request()->ip();
        $allowedIps = $config['allowed_ips'] ?? [];
        $allowedRanges = $config['allowed_ranges'] ?? [];
        
        // Check exact IP matches
        if (in_array($userIp, $allowedIps)) {
            return null;
        }
        
        // Check IP ranges
        foreach ($allowedRanges as $range) {
            if ($this->ipInRange($userIp, $range)) {
                return null;
            }
        }
        
        return [
            'constraint_type' => AttendanceConstraint::TYPE_LOCATION,
            'violation_type' => AttendanceConstraintViolation::TYPE_LOCATION_VIOLATION,
            'severity' => AttendanceConstraintViolation::SEVERITY_MEDIUM,
            'message' => 'Access from unauthorized IP address',
            'details' => [
                'user_ip' => $userIp,
                'allowed_ips' => $allowedIps,
                'allowed_ranges' => $allowedRanges
            ]
        ];
    }

    /**
     * Validate time-based constraints.
     */
    protected function validateTimeConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData): ?array
    {
        $config = $constraint->constraint_config;
        
        switch ($constraint->constraint_name) {
            case AttendanceConstraint::TIME_SHIFT_ENFORCEMENT:
                return $this->validateShiftEnforcement($attendance, $config);
            
            case AttendanceConstraint::TIME_EARLY_PREVENTION:
                return $this->validateEarlyPrevention($attendance, $config);
            
            case AttendanceConstraint::TIME_OVERTIME_APPROVAL:
                return $this->validateOvertimeApproval($attendance, $config);
            
            default:
                return null;
        }
    }

    /**
     * Validate shift enforcement constraint.
     */
    protected function validateShiftEnforcement(Attendance $attendance, array $config): ?array
    {
        $shiftStart = Carbon::parse($config['shift_start_time']);
        $shiftEnd = Carbon::parse($config['shift_end_time']);
        $gracePeriod = $config['grace_period_minutes'] ?? 0;
        
        $clockInTime = $attendance->clock_in_time;
        
        if ($clockInTime->lt($shiftStart->subMinutes($gracePeriod))) {
            return [
                'constraint_type' => AttendanceConstraint::TYPE_TIME,
                'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
                'severity' => AttendanceConstraintViolation::SEVERITY_MEDIUM,
                'message' => 'Clock-in time is before allowed shift start time',
                'details' => [
                    'clock_in_time' => $clockInTime->toISOString(),
                    'shift_start_time' => $shiftStart->toISOString(),
                    'grace_period_minutes' => $gracePeriod
                ]
            ];
        }
        
        return null;
    }

    /**
     * Validate device-based constraints.
     */
    protected function validateDeviceConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData): ?array
    {
        $config = $constraint->constraint_config;
        
        switch ($constraint->constraint_name) {
            case AttendanceConstraint::DEVICE_AUTHORIZED_ONLY:
                return $this->validateAuthorizedDevice($attendance, $config, $requestData);
            
            case AttendanceConstraint::DEVICE_SINGLE_POLICY:
                return $this->validateSingleDevicePolicy($attendance, $config, $requestData);
            
            default:
                return null;
        }
    }

    /**
     * Create a constraint violation record.
     */
    public function createViolation(Attendance $attendance, AttendanceConstraint $constraint, array $violationData): AttendanceConstraintViolation
    {
        return AttendanceConstraintViolation::create([
            'company_id' => $attendance->company_id,
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'constraint_id' => $constraint->id,
            'violation_type' => $violationData['violation_type'],
            'violation_details' => $violationData['details'] ?? [],
            'severity_level' => $violationData['severity'],
            'status' => AttendanceConstraintViolation::STATUS_PENDING,
            'auto_resolved' => false,
            'notification_sent' => false,
        ]);
    }

    /**
     * Process all violations and create records.
     */
    public function processViolations(Attendance $attendance, array $violations): Collection
    {
        $violationRecords = collect();
        
        foreach ($violations as $violation) {
            $constraint = AttendanceConstraint::find($violation['constraint_id']);
            if ($constraint) {
                $violationRecord = $this->createViolation($attendance, $constraint, $violation);
                $violationRecords->push($violationRecord);
                
                // Log the violation
                Log::warning('Attendance constraint violation detected', [
                    'user_id' => $attendance->user_id,
                    'constraint_type' => $violation['constraint_type'],
                    'violation_type' => $violation['violation_type'],
                    'severity' => $violation['severity'],
                    'message' => $violation['message']
                ]);
            }
        }
        
        return $violationRecords;
    }

    /**
     * Calculate distance between two coordinates (Haversine formula).
     */
    protected function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }

    /**
     * Check if IP is within a given range.
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $mask) = explode('/', $range);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Additional validation methods would be implemented here for:
     * - validateOfficeVerification
     * - validateEarlyPrevention
     * - validateOvertimeApproval
     * - validateAuthorizedDevice
     * - validateSingleDevicePolicy
     * - validateRoleConstraint
     * - validateBehavioralConstraint
     * - validateSecurityConstraint
     * - validateComplianceConstraint
     */
}

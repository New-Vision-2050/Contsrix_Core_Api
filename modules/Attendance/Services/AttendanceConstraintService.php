<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;
use InvalidArgumentException;

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
        // Get user's branch if they belong to one
        $userBranch = $user->managementHierarchy;
        
        return AttendanceConstraint::where('company_id', $user->company_id)
            ->where(function ($query) use ($user, $userBranch) {
                // Company-wide constraints (no specific user, department, or branch)
                $query->whereNull('user_id')
                      ->whereNull('department_id')
                      ->whereNull('branch_id');
                
                // User-specific constraints
                $query->orWhere('user_id', $user->id);
                
                // Department-specific constraints
                if ($user->department_id) {
                    $query->orWhere('department_id', $user->department_id);
                }
                
                // Branch-specific constraints
                if ($userBranch) {
                    $query->orWhere('branch_id', $userBranch->id);
                    
                    // Include inherited constraints from parent branches
                    $query->orWhere(function ($subQuery) use ($userBranch) {
                        $subQuery->where('inherit_from_parent', true)
                                 ->whereIn('branch_id', $this->getParentBranchIds($userBranch));
                    });
                }
            })
            ->active()
            ->byPriority()
            ->get()
            ->filter(function ($constraint) {
                return $this->isConstraintValidForDate($constraint);
            });
    }

    /**
     * Get parent branch IDs for inheritance.
     */
    private function getParentBranchIds(ManagementHierarchy $branch): array
    {
        $parentIds = [];
        $currentBranch = $branch;
        
        // Traverse up the hierarchy to get all parent branches
        while ($currentBranch->parent_id) {
            $parent = ManagementHierarchy::find($currentBranch->parent_id);
            if ($parent && $parent->type === 'branch') {
                $parentIds[] = $parent->id;
                $currentBranch = $parent;
            } else {
                break;
            }
        }
        
        return $parentIds;
    }

    /**
     * Check if constraint is valid for the current date.
     */
    private function isConstraintValidForDate(AttendanceConstraint $constraint): bool
    {
        $today = Carbon::today();
        
        if ($constraint->start_date && $today->lt($constraint->start_date)) {
            return false;
        }
        
        if ($constraint->end_date && $today->gt($constraint->end_date)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get constraints for a specific branch (including inherited).
     */
    public function getConstraintsForBranch(string $branchId, string $companyId): Collection
    {
        $branch = ManagementHierarchy::find($branchId);
        
        if (!$branch || $branch->type !== 'branch') {
            return collect();
        }
        
        return AttendanceConstraint::where('company_id', $companyId)
            ->applicableToBranch($branchId)
            ->active()
            ->byPriority()
            ->get()
            ->filter(function ($constraint) {
                return $this->isConstraintValidForDate($constraint);
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
            
            case AttendanceConstraint::TIME_MULTIPLE_PERIODS:
                return $this->validateMultiplePeriods($attendance, $config);
            
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
     * Validate attendance data against applicable constraints without an Attendance object.
     * Used for pre-validation before clock-in.
     *
     * @param array $attendanceData Array containing user_id, clock_in_time, and other attendance data
     * @return array List of constraint violations if any
     */
    public function validateAttendanceData(array $attendanceData): array
    {
        $violations = [];
        $user = User::find($attendanceData['user_id']);
        
        if (!$user) {
            return [
                [
                    'violation_type' => 'system_error',
                    'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
                    'message' => 'User not found',
                    'details' => ['user_id' => $attendanceData['user_id']]
                ]
            ];
        }
        
        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($user);
        
        foreach ($constraints as $constraint) {
            // Create a temporary attendance object for validation
            $tempAttendance = new Attendance([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'clock_in_time' => Carbon::parse($attendanceData['clock_in_time']),
                'clock_in_location' => $attendanceData['clock_in_location'] ?? null,
                'ip_address' => $attendanceData['ip_address'] ?? null
            ]);
            
            $violation = $this->validateSingleConstraint($tempAttendance, $constraint, $attendanceData);
            
            if ($violation) {
                $violation['constraint_id'] = $constraint->id;
                $violations[] = $violation;
            }
        }
        
        return $violations;
    }
    
    /**
     * Validate multiple periods constraint.
     */
    protected function validateMultiplePeriods(Attendance $attendance, array $config): ?array
    {
        try {
            // Parse config using data class for type safety
            $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);
        } catch (InvalidArgumentException $e) {
            return [
                'constraint_type' => AttendanceConstraint::TYPE_TIME,
                'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
                'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
                'message' => 'Invalid multiple periods configuration: ' . $e->getMessage(),
                'details' => [
                    'config_error' => $e->getMessage(),
                    'clock_in_time' => $attendance->clock_in_time->format('H:i'),
                ],
            ];
        }

        $clockInTime = $attendance->clock_in_time;
        $dayOfWeek = strtolower($clockInTime->format('l')); // e.g., 'sunday', 'monday'
        
        // Get the day schedule
        $daySchedule = $multiplePeriodsConfig->getDaySchedule($dayOfWeek);
        
        // Check if the day is configured
        if ($daySchedule === null) {
            return [
                'constraint_type' => AttendanceConstraint::TYPE_TIME,
                'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
                'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
                'message' => "No schedule configured for {$dayOfWeek}",
                'details' => [
                    'day_of_week' => $dayOfWeek,
                    'clock_in_time' => $clockInTime->format('H:i'),
                    'configured_days' => $multiplePeriodsConfig->getEnabledDays(),
                ],
            ];
        }
        
        // Check if the day is enabled
        if (!$daySchedule->enabled) {
            return [
                'constraint_type' => AttendanceConstraint::TYPE_TIME,
                'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
                'severity' => AttendanceConstraintViolation::SEVERITY_HIGH,
                'message' => "Attendance not allowed on {$dayOfWeek}",
                'details' => [
                    'day_of_week' => $dayOfWeek,
                    'clock_in_time' => $clockInTime->format('H:i'),
                    'enabled_days' => $multiplePeriodsConfig->getEnabledDays(),
                ],
            ];
        }
        
        // Check if clock-in time falls within any allowed period
        $clockInTimeString = $clockInTime->format('H:i');
        
        foreach ($daySchedule->periods as $period) {
            if ($this->isTimeWithinPeriod($clockInTimeString, $period)) {
                // Valid clock-in time found
                return null;
            }
        }
        
        // No valid period found
        return [
            'constraint_type' => AttendanceConstraint::TYPE_TIME,
            'violation_type' => AttendanceConstraintViolation::TYPE_TIME_VIOLATION,
            'severity' => AttendanceConstraintViolation::SEVERITY_MEDIUM,
            'message' => "Clock-in time outside allowed periods for {$dayOfWeek}",
            'details' => [
                'day_of_week' => $dayOfWeek,
                'clock_in_time' => $clockInTimeString,
                'allowed_periods' => array_map(function($period) {
                    return [
                        'name' => $period->name,
                        'start_time' => $period->startTime,
                        'end_time' => $period->endTime,
                        'spans_next_day' => $period->spansNextDay,
                        'effective_start' => $period->getEffectiveStartTime(),
                        'effective_end' => $period->getEffectiveEndTime(),
                    ];
                }, $daySchedule->periods),
            ],
        ];
    }

    /**
     * Check if a time falls within a period (including grace periods).
     */
    protected function isTimeWithinPeriod(string $timeString, $period): bool
    {
        // Convert time to minutes since midnight
        $timeMinutes = $this->timeToMinutes($timeString);
        
        // Get effective period boundaries including grace periods
        $effectiveStartMinutes = $this->timeToMinutes($period->getEffectiveStartTime());
        $effectiveEndMinutes = $this->timeToMinutes($period->getEffectiveEndTime());
        
        if ($period->spansNextDay) {
            // For cross-day periods, check if time is after start OR before end
            return $timeMinutes >= $effectiveStartMinutes || $timeMinutes <= $effectiveEndMinutes;
        } else {
            // For same-day periods, check if time is between start and end
            // Handle grace period overflow/underflow
            if ($effectiveStartMinutes > $effectiveEndMinutes) {
                // Grace periods caused overflow (e.g., 23:45 to 00:15 next day)
                return $timeMinutes >= $effectiveStartMinutes || $timeMinutes <= $effectiveEndMinutes;
            } else {
                return $timeMinutes >= $effectiveStartMinutes && $timeMinutes <= $effectiveEndMinutes;
            }
        }
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

    /**
     * Convert HH:MM time string to minutes since midnight.
     */
    protected function timeToMinutes(string $timeString): int
    {
        if (!preg_match('/^(\d{2}):(\d{2})$/', $timeString, $parts)) {
            // Return 0 or throw an exception for invalid format
            return 0;
        }
        return (int)$parts[1] * 60 + (int)$parts[2];
    }
}

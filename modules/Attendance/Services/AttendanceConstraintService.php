<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Contracts\TimeConstraintServiceInterface;
use Modules\Attendance\Contracts\LocationConstraintServiceInterface;
use Modules\Attendance\Contracts\DeviceConstraintServiceInterface;
use Modules\Attendance\Contracts\RoleConstraintServiceInterface;
use Modules\Attendance\Contracts\BehavioralConstraintServiceInterface;
use Modules\Attendance\Contracts\SecurityConstraintServiceInterface;
use Modules\Attendance\Contracts\ComplianceConstraintServiceInterface;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\AttendanceConstraintViolation;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Main attendance constraint service that acts as a facade coordinating specialized constraint services.
 * This service maintains backward compatibility while delegating validation to specialized services.
 */
class AttendanceConstraintService
{
    protected TimeConstraintServiceInterface $timeConstraintService;
    protected LocationConstraintServiceInterface $locationConstraintService;
    protected DeviceConstraintServiceInterface $deviceConstraintService;
    protected RoleConstraintServiceInterface $roleConstraintService;
    protected BehavioralConstraintServiceInterface $behavioralConstraintService;
    protected SecurityConstraintServiceInterface $securityConstraintService;
    protected ComplianceConstraintServiceInterface $complianceConstraintService;

    public function __construct(
        TimeConstraintServiceInterface $timeConstraintService,
        LocationConstraintServiceInterface $locationConstraintService,
        DeviceConstraintServiceInterface $deviceConstraintService,
        RoleConstraintServiceInterface $roleConstraintService,
        BehavioralConstraintServiceInterface $behavioralConstraintService,
        SecurityConstraintServiceInterface $securityConstraintService,
        ComplianceConstraintServiceInterface $complianceConstraintService
    ) {
        $this->timeConstraintService = $timeConstraintService;
        $this->locationConstraintService = $locationConstraintService;
        $this->deviceConstraintService = $deviceConstraintService;
        $this->roleConstraintService = $roleConstraintService;
        $this->behavioralConstraintService = $behavioralConstraintService;
        $this->securityConstraintService = $securityConstraintService;
        $this->complianceConstraintService = $complianceConstraintService;
    }

    /**
     * Validate attendance against all applicable constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $requestData Additional request data for validation
     * @return array Array of violations found during validation
     */
    public function validateAttendance(Attendance $attendance, array $requestData = []): array
    {
        $violations = [];
        $user = $attendance->user;
        
        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($user);
        
        foreach ($constraints as $constraint) {
            try {
                $violation = $this->validateSingleConstraint($attendance, $constraint, $requestData);
                if ($violation) {
                    $violations[] = $violation;
                    
                    // Create violation record
                    $this->createViolationRecord($attendance, $constraint, $violation);
                    
                    // Check if this violation should block attendance
                    if ($this->shouldBlockAttendance($constraint, $violation)) {
                        // Add blocking flag to violation
                        $violation['blocks_attendance'] = true;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error validating constraint', [
                    'constraint_id' => $constraint->id,
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $violations;
    }

    /**
     * Get all constraints applicable to a user.
     * 
     * @param User $user The user to get constraints for
     * @return Collection Collection of applicable constraints
     */
    public function getApplicableConstraints(User $user): Collection
    {
        $userBranch = $user->managementHierarchy;
        
        return AttendanceConstraint::where('company_id', $user->company_id)
            ->where(function ($query) use ($user, $userBranch) {
                // Global constraints (no branch restrictions)
                $query->whereNull('branch_locations')
                    ->orWhere('branch_locations', '[]')
                    ->orWhere('branch_locations', '');
                
                // Branch-specific constraints
                if ($userBranch) {
                    $query->orWhereJsonContains('branch_locations', [
                        'branch_id' => $userBranch->id
                    ]);
                }
            })
            ->where('is_active', true)
            ->get();
    }

    /**
     * Validate a single constraint against attendance.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param AttendanceConstraint $constraint The constraint to validate against
     * @param array $requestData Additional request data for validation
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateSingleConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData = []): bool|array
    {
        $config = $constraint->config ?? [];
        
        // Delegate to appropriate specialized service based on constraint type
        switch ($constraint->type) {
            case AttendanceConstraint::TYPE_TIME:
                return $this->timeConstraintService->validateTimeConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_LOCATION:
                return $this->locationConstraintService->validateLocationConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_DEVICE:
                return $this->deviceConstraintService->validateDeviceConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_ROLE:
                return $this->roleConstraintService->validateRoleConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_BEHAVIORAL:
                return $this->behavioralConstraintService->validateBehavioralConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_SECURITY:
                return $this->securityConstraintService->validateSecurityConstraint($attendance, $config);
                
            case AttendanceConstraint::TYPE_COMPLIANCE:
                return $this->complianceConstraintService->validateComplianceConstraint($attendance, $config);
                
            default:
                Log::warning('Unknown constraint type encountered', [
                    'constraint_id' => $constraint->id,
                    'type' => $constraint->type
                ]);
                return false;
        }
    }

    /**
     * Validate constraints before clock-in (pre-validation).
     * 
     * @param User $user The user attempting to clock in
     * @param array $requestData Request data including location, device info, etc.
     * @return array Array of violations that would prevent clock-in
     */
    public function validatePreClockIn(User $user, array $requestData = []): array
    {
        $violations = [];
        $constraints = $this->getApplicableConstraints($user);
        
        foreach ($constraints as $constraint) {
            // Only validate constraints that can be checked before attendance record creation
            if ($this->canValidatePreClockIn($constraint)) {
                try {
                    // Create a temporary attendance object for validation
                    $tempAttendance = new Attendance([
                        'user_id' => $user->id,
                        'clock_in_time' => now(),
                        'location' => $requestData['location'] ?? null,
                        'device_info' => $requestData['device_info'] ?? null,
                    ]);
                    $tempAttendance->user = $user;
                    
                    $violation = $this->validateSingleConstraint($tempAttendance, $constraint, $requestData);
                    if ($violation) {
                        $violations[] = $violation;
                    }
                } catch (\Exception $e) {
                    Log::error('Error in pre-clock-in validation', [
                        'constraint_id' => $constraint->id,
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $violations;
    }

    /**
     * Check if a constraint can be validated before clock-in.
     * 
     * @param AttendanceConstraint $constraint The constraint to check
     * @return bool True if constraint can be pre-validated
     */
    private function canValidatePreClockIn(AttendanceConstraint $constraint): bool
    {
        // Constraints that can be validated before creating attendance record
        $preValidatableTypes = [
            AttendanceConstraint::TYPE_TIME,
            AttendanceConstraint::TYPE_LOCATION,
            AttendanceConstraint::TYPE_DEVICE,
            AttendanceConstraint::TYPE_ROLE,
            AttendanceConstraint::TYPE_SECURITY,
        ];
        
        return in_array($constraint->type, $preValidatableTypes);
    }

    /**
     * Create a violation record for tracking and reporting.
     * 
     * @param Attendance $attendance The attendance record
     * @param AttendanceConstraint $constraint The violated constraint
     * @param array $violationDetails Details of the violation
     * @return AttendanceConstraintViolation The created violation record
     */
    public function createViolationRecord(Attendance $attendance, AttendanceConstraint $constraint, array $violationDetails): AttendanceConstraintViolation
    {
        return AttendanceConstraintViolation::create([
            'attendance_id' => $attendance->id,
            'constraint_id' => $constraint->id,
            'user_id' => $attendance->user_id,
            'company_id' => $attendance->user->company_id,
            'violation_type' => $violationDetails['constraint_type'] ?? $constraint->type,
            'severity' => $violationDetails['severity'] ?? 'medium',
            'message' => $violationDetails['message'] ?? 'Constraint violation detected',
            'details' => $violationDetails['details'] ?? [],
            'status' => 'pending',
            'detected_at' => now(),
        ]);
    }

    /**
     * Check if a violation should block attendance.
     * 
     * @param AttendanceConstraint $constraint The constraint that was violated
     * @param array $violation The violation details
     * @return bool True if attendance should be blocked
     */
    private function shouldBlockAttendance(AttendanceConstraint $constraint, array $violation): bool
    {
        // Block attendance for high severity violations
        if (($violation['severity'] ?? 'medium') === 'high') {
            return true;
        }
        
        // Block attendance for specific constraint types that are critical
        $blockingTypes = [
            AttendanceConstraint::TYPE_SECURITY,
            AttendanceConstraint::TYPE_COMPLIANCE,
        ];
        
        return in_array($constraint->type, $blockingTypes);
    }

    /**
     * Resolve a constraint violation.
     * 
     * @param int $violationId The violation ID to resolve
     * @param string $resolution Resolution notes
     * @param int $resolvedBy User ID of who resolved the violation
     * @return bool True if successfully resolved
     */
    public function resolveViolation(int $violationId, string $resolution, int $resolvedBy): bool
    {
        $violation = AttendanceConstraintViolation::find($violationId);
        
        if (!$violation) {
            return false;
        }
        
        $violation->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);
        
        return true;
    }

    /**
     * Dismiss a constraint violation.
     * 
     * @param int $violationId The violation ID to dismiss
     * @param string $reason Reason for dismissal
     * @param int $dismissedBy User ID of who dismissed the violation
     * @return bool True if successfully dismissed
     */
    public function dismissViolation(int $violationId, string $reason, int $dismissedBy): bool
    {
        $violation = AttendanceConstraintViolation::find($violationId);
        
        if (!$violation) {
            return false;
        }
        
        $violation->update([
            'status' => 'dismissed',
            'resolution' => $reason,
            'resolved_by' => $dismissedBy,
            'resolved_at' => now(),
        ]);
        
        return true;
    }

    /**
     * Get violation statistics for a company.
     * 
     * @param int $companyId The company ID
     * @param Carbon|null $startDate Start date for statistics
     * @param Carbon|null $endDate End date for statistics
     * @return array Statistics array
     */
    public function getViolationStatistics(int $companyId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = AttendanceConstraintViolation::where('company_id', $companyId);
        
        if ($startDate) {
            $query->where('detected_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('detected_at', '<=', $endDate);
        }
        
        $violations = $query->get();
        
        return [
            'total_violations' => $violations->count(),
            'by_severity' => [
                'high' => $violations->where('severity', 'high')->count(),
                'medium' => $violations->where('severity', 'medium')->count(),
                'low' => $violations->where('severity', 'low')->count(),
            ],
            'by_status' => [
                'pending' => $violations->where('status', 'pending')->count(),
                'resolved' => $violations->where('status', 'resolved')->count(),
                'dismissed' => $violations->where('status', 'dismissed')->count(),
            ],
            'by_type' => $violations->groupBy('violation_type')->map->count()->toArray(),
            'resolution_rate' => $violations->count() > 0 
                ? ($violations->whereIn('status', ['resolved', 'dismissed'])->count() / $violations->count()) * 100 
                : 0,
        ];
    }

    /**
     * Get violations for a specific user.
     * 
     * @param int $userId The user ID
     * @param string|null $status Filter by status
     * @param int $limit Number of violations to return
     * @return Collection Collection of violations
     */
    public function getUserViolations(int $userId, ?string $status = null, int $limit = 50): Collection
    {
        $query = AttendanceConstraintViolation::where('user_id', $userId)
            ->with(['attendance', 'constraint'])
            ->orderBy('detected_at', 'desc');
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->limit($limit)->get();
    }

    /**
     * Get violations for a specific attendance record.
     * 
     * @param int $attendanceId The attendance ID
     * @return Collection Collection of violations
     */
    public function getAttendanceViolations(int $attendanceId): Collection
    {
        return AttendanceConstraintViolation::where('attendance_id', $attendanceId)
            ->with(['constraint'])
            ->orderBy('detected_at', 'desc')
            ->get();
    }
}

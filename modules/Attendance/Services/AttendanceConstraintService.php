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
    public function validateAttendance(Attendance $attendance, array $requestData = [],bool $isDryRun = false): array
    {
        $violations = [];
        $user = $attendance->user;

        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($user);
        if (!$isDryRun && $attendance->exists) {
            $appliedConstraintIds = $constraints->pluck('id')
                                                 ->map(fn($id) => (string) $id)
                                                 ->all();
            $attendance->appliedConstraints()->sync($appliedConstraintIds);
        }
        foreach ($constraints as $constraint) {
            try {

                $violation = $this->validateSingleConstraint($attendance, $constraint, $requestData,$isDryRun);

                if ($violation) {
                    $violations[] = $violation;

                    if (!$isDryRun && $attendance->exists) {
                        // We pass the full constraint object here
                        $this->createViolation($attendance, $constraint, $violation);
                    }
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
        $userBranch = $user->branch ?? null;
        $userBranchId = $userBranch ? (string) $userBranch->id : null;

        $constraint = AttendanceConstraint::where('company_id', $user->company_id)
    ->where(function ($query) use ($user, $userBranchId) {
                $query->where(function ($q) {
                    $q->whereNull('user_id')
                      ->where(function ($subQ) {
                          $subQ->whereNull('branch_ids')
                               ->orWhereJsonLength('branch_ids', 0);
                      });
                })
                ->orWhere('user_id', $user->id);
                $query->when($userBranchId, function ($q) use ($userBranchId) {
                    $q->orWhereJsonContains('branch_ids', $userBranchId);
                });
            })
            ->where('is_active', true)
            ->get();

        return $constraint;
    }

    /**
     * Validate a single constraint against attendance.
     *
     * This updated version treats a single constraint as a "ruleset" which can contain
     * configurations for multiple rule types (e.g., location, time, device). It checks for the
     * presence of specific keys within the 'constraint_config' JSON to determine which
     * validations to run.
     *
     * @param Attendance $attendance The attendance record to validate.
     * @param AttendanceConstraint $constraint The constraint object containing the ruleset.
     * @param array $requestData Additional request data (currently unused here but kept for interface compatibility).
     * @return bool|array Returns false if all applicable checks pass, or an array with details of the first violation found.
     */
    public function validateSingleConstraint(Attendance $attendance, AttendanceConstraint $constraint, array $requestData = [],bool $isDryRun = false): bool|array
    {
        // Get the entire configuration object for the constraint.
        $config = $constraint->constraint_config ?? [];

        if (!empty($constraint->branch_locations) || isset($config['location_rules'])) {
            $violation = $this->locationConstraintService->validateLocationConstraint($attendance, $constraint);
     
            if ($violation) {

                return $violation;
            }
        }


        // Define a map linking config keys to their respective validation services.
        // This makes the code cleaner and easier to extend.
        $validationMap = [
            'time_rules'       => fn() => $this->timeConstraintService->validateTimeConstraint($attendance, $config['time_rules']),
            // 'location_rules'   => fn() => $this->locationConstraintService->validateLocationConstraint($attendance, $constraint), // This service expects the full constraint
            'device_rules'     => fn() => $this->deviceConstraintService->validateDeviceConstraint($attendance, $config['device_rules']),
            'behavioral_rules' => fn() => $this->behavioralConstraintService->validateBehavioralConstraint($attendance, $config['behavioral_rules']),
            'security_rules'   => fn() => $this->securityConstraintService->validateSecurityConstraint($attendance, $config['security_rules']),
            'compliance_rules' => fn() => $this->complianceConstraintService->validateComplianceConstraint($attendance, $config['compliance_rules']),
            'role_rules'       => fn() => $this->roleConstraintService->validateRoleConstraint($attendance, $config['role_rules']),
        ];

        // Iterate through the map and execute validation for any rules present in the config.
        foreach ($validationMap as $configKey => $validationFunction) {
            // Check if the specific rule configuration exists in the constraint.
            if (isset($config[$configKey])) {
                // Execute the corresponding validation function.
                $violation = $validationFunction();
                // If a violation is found, stop immediately and return it.
                if ($violation) {
                    $violation['constraint_id'] = $constraint->id;

                    return $violation;
                }
            }
        }

        // If the loop completes without finding any violations, all checks have passed.
        return false;
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

    /**
     * Validate break end against constraints.
     *
     * @param Attendance $attendance The attendance record with break end
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBreakEnd(Attendance $attendance): bool|array
    {
        if (!$attendance->break_start_time || !$attendance->break_end_time) {
            return false;
        }

        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($attendance->user);

        // Filter for break-related constraints
        $breakConstraints = $constraints->filter(function ($constraint) {
            return $constraint->type === AttendanceConstraint::TYPE_TIME &&
                   ($constraint->config['subtype'] ?? '') === AttendanceConstraint::TIME_BREAK_LIMITS;
        });

        if ($breakConstraints->isEmpty()) {
            return false;
        }

        // Check each break constraint
        foreach ($breakConstraints as $constraint) {
            $config = $constraint->config ?? [];

            // Calculate break duration
            $breakStartTime = Carbon::parse($attendance->break_start_time);
            $breakEndTime = Carbon::parse($attendance->break_end_time);
            $breakDurationMinutes = $breakStartTime->diffInMinutes($breakEndTime);

            // Check if break duration exceeds maximum allowed
            $maxBreakDuration = (int)($config['max_break_duration_minutes'] ?? 0);
            if ($maxBreakDuration > 0 && $breakDurationMinutes > $maxBreakDuration) {
                return [
                    'constraint_id' => $constraint->id,
                    'constraint_type' => AttendanceConstraint::TIME_BREAK_LIMITS,
                    'severity' => $config['severity'] ?? 'medium',
                    'message' => "Break duration ({$breakDurationMinutes} minutes) exceeds maximum allowed ({$maxBreakDuration} minutes)",
                    'details' => [
                        'break_start_time' => $breakStartTime->toDateTimeString(),
                        'break_end_time' => $breakEndTime->toDateTimeString(),
                        'break_duration_minutes' => $breakDurationMinutes,
                        'max_allowed_minutes' => $maxBreakDuration,
                        'excess_minutes' => $breakDurationMinutes - $maxBreakDuration
                    ]
                ];
            }

            // Check if minimum break duration is enforced
            $minBreakDuration = (int)($config['min_break_duration_minutes'] ?? 0);
            if ($minBreakDuration > 0 && $breakDurationMinutes < $minBreakDuration) {
                return [
                    'constraint_id' => $constraint->id,
                    'constraint_type' => AttendanceConstraint::TIME_BREAK_LIMITS,
                    'severity' => $config['severity'] ?? 'low',
                    'message' => "Break duration ({$breakDurationMinutes} minutes) is less than minimum required ({$minBreakDuration} minutes)",
                    'details' => [
                        'break_start_time' => $breakStartTime->toDateTimeString(),
                        'break_end_time' => $breakEndTime->toDateTimeString(),
                        'break_duration_minutes' => $breakDurationMinutes,
                        'min_required_minutes' => $minBreakDuration,
                        'shortage_minutes' => $minBreakDuration - $breakDurationMinutes
                    ]
                ];
            }
        }

        return false;
    }

    public function createViolation(Attendance $attendance, AttendanceConstraint $constraint, array $violationData): AttendanceConstraintViolation
    {
        return AttendanceConstraintViolation::create([
            'attendance_id' => $attendance->id,
            'constraint_id' => $constraint->id,
            'user_id' => $attendance->user_id,
            'company_id' => $attendance->company_id,
            'violation_type' => $violationData['constraint_type'] ?? $constraint->type,
            'severity' => $violationData['severity'] ?? 'medium',
            'message' => $violationData['message'] ?? 'Constraint violation detected',
            'details' => $violationData['details'] ?? [],
            'status' => 'pending',
            'detected_at' => now(),
        ]);
    }
public function getTodaysWorkRulesForUser(User $user): array
    {
        $timezone = getTimeZoneByRequest()?? config('app.timezone');

        $now = Carbon::now($timezone);

        $constraints = $this->getApplicableConstraints($user);

        // Define a reusable closure to select the winning constraint based on priority.
    $selectWinningConstraint = function (callable $filter) use ($constraints, $user) {
            return $constraints
                ->filter($filter)
                ->sortByDesc(function ($constraint) use ($user) {
                    $score = ($constraint->priority ?? 1) * 100;

                    if ($constraint->user_id === $user->id) {
                        $score += 10000;
                    }
                    elseif (!empty($constraint->branch_ids)) {
                        $score += 1000;
                    }
                    return $score;
                })
                ->sortByDesc('created_at')
                ->first();
        };

        // Find the winning TIME and LOCATION constraints.
        $timeConstraint = $selectWinningConstraint(fn($c) => isset($c->constraint_config['time_rules']));
        $locationConstraint = $selectWinningConstraint(fn($c) => !empty($c->branch_locations) || isset($c->constraint_config['location_rules']));
        // Build the rule summaries from the winning constraints.
        $timeRulesResult = $this->buildTimeRules($timeConstraint, $now);
        $locationRulesResult = $this->buildLocationRules($locationConstraint, $user);

        // Combine the results into a final, clean response.
        return [
            'day_status'              => $timeRulesResult['status'],
            'day_name'                => $now->isoFormat(format: 'dddd'),
            'is_holiday'              => $timeRulesResult['is_holiday'],
            'reason'                  => $timeRulesResult['reason'],
            'todays_work_periods'     => $timeRulesResult['periods'],
            'todays_total_work_hours' => $timeRulesResult['total_work_hours'],
            'active_or_next_period'   => $timeRulesResult['active_or_next_period'],
            'location_work'           => $locationRulesResult,
            'source_constraint_ids'   => [
                'time' => $timeConstraint?->id,
                'location' => $locationConstraint?->id,
            ],
        ];
    }

    /**
     * Validate an attendance record against all applicable constraints.
     *
     * @param Attendance $attendance The attendance record to validate.
     * @param array $requestData Additional request data.
     * @param bool $isDryRun If true, will not create violation or applied_constraint records.
     * @return array Array of violations found.
     */

    private function buildTimeRules(?AttendanceConstraint $constraint, Carbon $now): array
    {
        $defaultResult = ['status' => 'Undefined', 'reason' => 'No time schedule applied.', 'periods' => [], 'is_holiday' => false, 'total_work_hours' => 0.0, 'active_or_next_period' => null];
        if (!$constraint) return $defaultResult;

        $timeRules = $constraint->constraint_config['time_rules'] ?? [];
        $weeklySchedule = $timeRules['weekly_schedule'] ?? [];
        $holidays = $timeRules['holidays'] ?? [];

        $dayOfWeek = strtolower($now->format('l'));
        $isTodayHoliday = collect($holidays)->contains(fn($h) => $now->isSameDay($h['date'] ?? null));
        $todaySchedule = $weeklySchedule[$dayOfWeek] ?? ['enabled' => false];
        $isTodayWorkDay = !$isTodayHoliday && ($todaySchedule['enabled'] ?? false);

        if ($isTodayHoliday) {
            $workDayStatus = 'Holiday';
            $workDayReason = collect($holidays)->firstWhere(fn($h) => $now->isSameDay($h['date'] ?? null))['name'] ?? 'Official Holiday';
        } elseif ($isTodayWorkDay) {
            $workDayStatus = 'Work Day';
            $workDayReason = 'Scheduled working day.';
        } else {
            $workDayStatus = 'Day Off';
            $workDayReason = 'Scheduled weekend or non-working day.';
        }

        $relevantPeriod = null;
        for ($i = 0; $i < 7; $i++) {
            $checkDate = $now->copy()->addDays($i);
            $checkDayOfWeek = strtolower($checkDate->format('l'));

            if (collect($holidays)->contains(fn($h) => $checkDate->isSameDay($h['date'] ?? null))) continue;
            $dayScheduleForCheck = $weeklySchedule[$checkDayOfWeek] ?? null;
            if (!$dayScheduleForCheck || !($dayScheduleForCheck['enabled'] ?? false) || empty($dayScheduleForCheck['periods'])) continue;

            foreach ($dayScheduleForCheck['periods'] as $period) {
                $periodStart = Carbon::createFromTimeString($period['start_time'], $now->timezone)->setDateFrom($checkDate);
                $periodEnd = Carbon::createFromTimeString($period['end_time'], $now->timezone)->setDateFrom($checkDate);
                if ($periodEnd->isBefore($periodStart)) $periodEnd->addDay();

                if ($now->between($periodStart, $periodEnd, true)) {
                    $relevantPeriod = ['status' => 'active', 'day' => 'Today', 'date' => $checkDate->format('Y-m-d')] + $period;
                    goto end_loop;
                }

                if ($periodStart->isFuture()) {
                    $relevantPeriod = ['status' => 'upcoming', 'day' => $i === 0 ? 'Today' : $checkDate->isoFormat('dddd'), 'date' => $checkDate->format('Y-m-d')] + $period;
                    goto end_loop;
                }
            }
        }
        end_loop:

        return [
            'status' => $workDayStatus,
            'reason' => $workDayReason,
            'periods' => $todaySchedule['periods'] ?? [],
            'is_holiday' => ($workDayStatus !== 'Work Day'),
            'total_work_hours' => (float)($todaySchedule['total_work_hours'] ?? 0.0),
            'active_or_next_period' => $relevantPeriod
        ];
    }

    private function buildLocationRules(?AttendanceConstraint $constraint, User $user): ?array
    {
        if (!$constraint || !$user->branch) return null;
        $userBranchId = (string) $user->branch->id;
        if (!empty($constraint->branch_locations)) {
            $branchData = collect($constraint->branch_locations)->firstWhere('branch_id', $userBranchId);
            if ($branchData) {
                return ['name' => $branchData['name'] ?? $user->branch->name] +
                       ['latitude' => (float)($branchData['latitude'] ?? 0), 'longitude' => (float)($branchData['longitude'] ?? 0), 'radius' => (int)($branchData['radius'] ?? 0)];
            }
        }
        $locationRules = $constraint->constraint_config['location_rules'] ?? [];
        if (!empty($locationRules['allowed_zones'])) {
            $firstZone = $locationRules['allowed_zones'][0];
            return ['name' => $firstZone['name'] ?? $user->branch->name] +
                   ['latitude' => (float)($firstZone['latitude'] ?? 0), 'longitude' => (float)($firstZone['longitude'] ?? 0), 'radius' => (int)($firstZone['radius'] ?? 0)];
        }
        return null;
    }
}

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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Attendance\DataClasses\WeeklySchedule;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use InvalidArgumentException;
use function Ramsey\Uuid\v1;

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
        // Get user ID from attendance to avoid readonly property issue
        $userId = $attendance->user_id;
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }
        
        // Get all applicable constraints for the user
        $constraints = $this->getApplicableConstraints($user);
        if ($constraints->isEmpty()) {

            return [
                'constraint_type' => 'none applied to user and no default constraint',
                'severity' => $config['severity'] ?? 'high',
                'message' => 'Location data is required to validate against branch locations but was not provided.',
                'details' => ['reason' => 'Missing GPS data from user.']
            ];
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
    public function getEffectiveConstraintForUser(User $user)
    {
        // Use the same optimized logic as getApplicableConstraintsForDataRetrieval
        return $this->getApplicableConstraintsForDataRetrieval($user);
    }
  public function getApplicableConstraints(User $user): Collection
    {
       return $this->getEffectiveConstraintForUser($user);
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

        if (isset($config['time_rules'])) {
            $violation = $this->timeConstraintService->validateTimeConstraint($attendance, $config['time_rules']);
            if ($violation) {
                return $violation;
            }
        }

        // Define a map linking config keys to their respective validation services.
        // This makes the code cleaner and easier to extend.
        $validationMap = [

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

        if ($constraints->isEmpty()) {
            return [];
        }

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
        // Use the most recently completed break from attendance_breaks table.
        $lastBreak = $attendance->breaks()
            ->whereNotNull('end_time')
            ->latest('end_time')
            ->first();

        if (!$lastBreak) {
            return false;
        }

        $user = User::find($attendance->user_id);
        if (!$user) {
            return false;
        }

        $constraints = $this->getApplicableConstraints($user);

        $breakConstraints = $constraints->filter(function ($constraint) {
            return $constraint->type === AttendanceConstraint::TYPE_TIME &&
                   ($constraint->config['subtype'] ?? '') === AttendanceConstraint::TIME_BREAK_LIMITS;
        });

        if ($breakConstraints->isEmpty()) {
            return false;
        }

        $breakStartTime       = $lastBreak->start_time instanceof Carbon
            ? $lastBreak->start_time
            : Carbon::parse($lastBreak->start_time);
        $breakEndTime         = $lastBreak->end_time instanceof Carbon
            ? $lastBreak->end_time
            : Carbon::parse($lastBreak->end_time);
        $breakDurationMinutes = (int) $lastBreak->duration_minutes
            ?: (int) $breakStartTime->diffInMinutes($breakEndTime);

        foreach ($breakConstraints as $constraint) {
            $config = $constraint->config ?? [];

            $maxBreakDuration = (int) ($config['max_break_duration_minutes'] ?? 0);
            if ($maxBreakDuration > 0 && $breakDurationMinutes > $maxBreakDuration) {
                return [
                    'constraint_id'   => $constraint->id,
                    'constraint_type' => AttendanceConstraint::TIME_BREAK_LIMITS,
                    'severity'        => $config['severity'] ?? 'medium',
                    'message'         => "Break duration ({$breakDurationMinutes} minutes) exceeds maximum allowed ({$maxBreakDuration} minutes)",
                    'details'         => [
                        'break_start_time'       => $breakStartTime->toDateTimeString(),
                        'break_end_time'         => $breakEndTime->toDateTimeString(),
                        'break_duration_minutes' => $breakDurationMinutes,
                        'max_allowed_minutes'    => $maxBreakDuration,
                        'excess_minutes'         => $breakDurationMinutes - $maxBreakDuration,
                    ],
                ];
            }

            $minBreakDuration = (int) ($config['min_break_duration_minutes'] ?? 0);
            if ($minBreakDuration > 0 && $breakDurationMinutes < $minBreakDuration) {
                return [
                    'constraint_id'   => $constraint->id,
                    'constraint_type' => AttendanceConstraint::TIME_BREAK_LIMITS,
                    'severity'        => $config['severity'] ?? 'low',
                    'message'         => "Break duration ({$breakDurationMinutes} minutes) is less than minimum required ({$minBreakDuration} minutes)",
                    'details'         => [
                        'break_start_time'       => $breakStartTime->toDateTimeString(),
                        'break_end_time'         => $breakEndTime->toDateTimeString(),
                        'break_duration_minutes' => $breakDurationMinutes,
                        'min_required_minutes'   => $minBreakDuration,
                        'shortage_minutes'       => $minBreakDuration - $breakDurationMinutes,
                    ],
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
    public function getTodaysWorkRulesForUser(User $user, $date = null, ?string $timezone = null): array
    {
        // Use the provided timezone or get it once to avoid multiple calls
        $timezone = $timezone ?? getTimeZoneBranchByRequest() ?? config('app.timezone');
        $now = $date
            ? Carbon::parse($date, $timezone)
            : Carbon::now($timezone);

        $constraints = $this->getApplicableConstraintsForDataRetrieval($user);
        
        if ($constraints->isEmpty()) {
            return [
                'day_status' => 'Undefined',
                'day_name' => $now->isoFormat(format: 'dddd'),
                'is_holiday' => false,
                'reason' => 'No attendance schedule applied.',
                'all_work_periods' => [],
                'total_work_hours' => 0.0,
                'next_work_period' => null,
                'current_work_period' => null,
                'lateness_rules' => null,
                'early_clock_in_rules' => null,
                'location_work' => null,
                'max_over_time' => null,
                'source_constraint_ids' => ['time' => null, 'location' => null],
            ];
        }

        // Define a reusable closure to select the winning constraint based on priority.
        // Assuming higher priority number means higher priority, then by creation date.
        $selectWinningConstraint = function (callable $filter) use ($constraints, $user) {
            return $constraints
                ->filter($filter)
                ->sortByDesc('priority') // Sort by priority if applicable
                ->sortByDesc('created_at') // Then by creation date for stability
                ->first();
        };

        // Find the winning TIME and LOCATION constraints.
        // These keys might need to be adjusted based on your AttendanceConstraint model structure
        $timeConstraint = $selectWinningConstraint(fn($c) => isset($c->constraint_config['time_rules']));
        $locationConstraint = $selectWinningConstraint(fn($c) => !empty($c->branch_locations) || isset($c->constraint_config['location_rules']));

        // Build the rule summaries from the winning constraints.
        $timeRulesResult = $this->buildTimeRules($timeConstraint, $now);
        $locationRulesResult = $this->buildLocationRules($locationConstraint, $user);

        // Combine the results into a final, clean response.
        return [
            'day_status'              => $timeRulesResult['day_status'],
            'day_name'                => $now->isoFormat(format: 'dddd'),
            'is_holiday'              => $timeRulesResult['is_holiday'],
            'reason'                  => $timeRulesResult['reason'],
            'all_work_periods'        => $timeRulesResult['periods'],
            'total_work_hours'        => $timeRulesResult['total_work_hours'],
            'current_work_period'     => $timeRulesResult['current_work_period'],
            'first_next_period'       => $timeRulesResult['first_next_period'],
            'second_next_period'      => $timeRulesResult['second_next_period'],
            'active_or_next_period'   => $timeRulesResult['active_or_next_period'],
            'lateness_rules'          => $timeRulesResult['lateness_rules'],
            'early_clock_in_rules'    => $timeRulesResult['early_clock_in_rules'],
            'location_work'           => $locationRulesResult,
            'max_over_time'           => $timeConstraint?->max_over_time,
            'source_constraint_ids'   => [
                'time' => $timeConstraint?->id,
                'location' => $locationConstraint?->id,
            ],
        ];
    }

    /**
     * Builds time-related attendance rules for a given constraint and date.
     * Includes periods spilling over from the previous day.
     *
     * @param AttendanceConstraint|null $constraint The time constraint to use.
     * @param Carbon $now The Carbon instance representing the current date/time.
     * @return array
     */
    private function buildTimeRules(?AttendanceConstraint $constraint, Carbon $now): array
    {
        $defaultResult = [
            'day_status' => 'Undefined',
            'reason' => 'No time schedule applied.',
            'periods' => [], // All active periods for today, including spillover
            'is_holiday' => true,
            'total_work_hours' => 0.0,
            'lateness_rules' => null,
            'early_clock_in_rules' => null,
            'current_work_period' => null,
            'first_next_period' => null,
            'second_next_period' => null,
            'active_or_next_period' => null,
        ];

        if (!$constraint) {
            return $defaultResult;
        }

        $timeRulesData = $constraint->constraint_config['time_rules'] ?? [];

        try {
            // Attempt to parse the weekly schedule using the DataClass
            $weeklySchedule = WeeklySchedule::fromArray($timeRulesData['weekly_schedule'] ?? []);
        } catch (InvalidArgumentException $e) {
            Log::error("Failed to parse WeeklySchedule for constraint {$constraint->id}: " . $e->getMessage());
            return array_merge($defaultResult, ['reason' => 'Invalid weekly schedule configuration: ' . $e->getMessage()]);
        }

        $holidays = $timeRulesData['holidays'] ?? [];
        $dayOfWeek = strtolower($now->format('l'));

        $isTodayHoliday = collect($holidays)->contains(fn($h) => $now->isSameDay($h['date'] ?? null));

        // Get today's schedule using the WeeklySchedule DataClass
        $todaySchedule = $weeklySchedule->getDaySchedule($dayOfWeek);

        if ($isTodayHoliday) {
            $workDayStatus = 'holiday';
            $workDayReason = collect($holidays)->firstWhere(fn($h) => $now->isSameDay($h['date'] ?? null))['name'] ?? 'Official Holiday';
        } elseif ($todaySchedule->enabled) {
            $workDayStatus = 'work_day';
            $workDayReason = 'Scheduled working day.';
        } else {
            $workDayStatus = 'day_off_or_weekend';
            $workDayReason = 'Scheduled weekend or non-working day.';
        }

        if ($workDayStatus !== 'work_day') {
             return array_merge($defaultResult, [
                'day_status' => $workDayStatus,
                'reason' => $workDayReason,
                'is_holiday' => true, // Always true when not a work day (holiday or weekend)
            ]);
        }

        // 2. Get periods from the previous day that extend into today
        $previousDay = $now->copy()->subDay();
        $previousDaySchedule = $weeklySchedule->getDaySchedule(strtolower($previousDay->format('l')));

        $spilloverPeriodsFromPreviousDay = [];
        if ($previousDaySchedule && $previousDaySchedule->enabled) {
            $spilloverPeriodsRaw = $previousDaySchedule->getPeriodsCrossingToNextDay(ucfirst(strtolower($previousDay->format('l'))));
            foreach ($spilloverPeriodsRaw as $spillover) {
                // Ensure correct Carbon objects for start/end in today's context
                $effectiveSpilloverStart = $now->copy()->startOfDay(); // Starts at 00:00 of today
                $effectiveSpilloverEnd = $now->copy()->startOfDay()->addMinutes($spillover['end_minutes']); // Ends at actual time in today

                $spilloverPeriodsFromPreviousDay[] = [
                    'status' => 'spillover',
                    'day' => ucfirst(strtolower($previousDay->format('l'))),
                    'date' => $previousDay->format('Y-m-d'), // Original date it started
                    'start_time' => $spillover['original_period_start'],
                    'end_time' => $spillover['original_period_end'],
                    'period_start_time_carbon' => $effectiveSpilloverStart,
                    'period_end_time_carbon' => $effectiveSpilloverEnd,
                    'extends_to_next_day' => true, // Always true for spillover
                    'original_day_name' => $spillover['original_day']
                ];
            }
        }

        // 3. Combine today's defined periods from the DaySchedule object
        $allTodaysRawPeriods = [];
        foreach ($todaySchedule->getPeriods() as $period) {
            $periodStartToday = Carbon::createFromTimeString($period->startTime, $now->timezone)->setDateFrom($now);
            $periodEndToday = Carbon::createFromTimeString($period->endTime, $now->timezone)->setDateFrom($now);

            if ($period->extends_to_next_day) {
                $periodEndToday->addDay(); // Adjust end time to next day if it spans
            }

            $allTodaysRawPeriods[] = [
                'status' => 'scheduled',
                'day' => 'Today',
                'date' => $now->format('Y-m-d'),
                'start_time' => $period->startTime,
                'end_time' => $period->endTime,
                'period_start_time_carbon' => $periodStartToday,
                'period_end_time_carbon' => $periodEndToday,
                'extends_to_next_day' => $period->extends_to_next_day,
            ];
        }

        // Merge all periods (spillover + today's) and sort them chronologically
        $allTodaysPeriods = collect(array_merge($spilloverPeriodsFromPreviousDay, $allTodaysRawPeriods))
            ->sortBy(fn($p) => $p['period_start_time_carbon']->timestamp)
            ->values()
            ->all();

        // Use the dedicated helper function to find current, first next, and second next periods
        $periodDetails = $this->getCurrentOrNextPeriodDetails($allTodaysPeriods, $now, $weeklySchedule);

        $lateness_rules = $todaySchedule->lateness_rules ?? null;
        $early_clock_in_rules = $todaySchedule->early_clock_in_rules ?? null;

        return [
            'day_status' => $workDayStatus,
            'reason' => $workDayReason,
            'periods' => $allTodaysPeriods, // All periods for today, merged and sorted
            'is_holiday' => ($workDayStatus === 'holiday'),
            'total_work_hours' => '', // Calculated by DaySchedule
            'current_work_period' => $periodDetails['current_period'] ?? $periodDetails['fallback_period'],
            'first_next_period' => $periodDetails['first_next_period'],
            'second_next_period' => $periodDetails['second_next_period'],
            'active_or_next_period' => $periodDetails['current_period'] ?? $periodDetails['first_next_period'] ?? $periodDetails['fallback_period'],
            'lateness_rules' => $lateness_rules,
            'early_clock_in_rules' => $early_clock_in_rules
        ];
    }

    /**
     * Determines the current active work period, the first upcoming, and the second upcoming work period.
     * Searches within today's schedule and extends to future work days if needed.
     *
     * @param array $allTodaysPeriods Merged and sorted periods relevant to $now's day.
     * @param Carbon $now Current Carbon instance.
     * @param WeeklySchedule $weeklySchedule Full weekly schedule for searching future days.
     * @return array An associative array containing 'current_period', 'first_next_period', 'second_next_period', 'fallback_period'.
     */
    private function getCurrentOrNextPeriodDetails(array $allTodaysPeriods, Carbon $now, WeeklySchedule $weeklySchedule): array
    {
        $currentPeriod = null;
        $firstNextPeriod = null;
        $secondNextPeriod = null;
        $nowTimestamp = $now->timestamp;

        $foundCurrent = false;
        $foundFirstNext = false;

        // Phase 1: Find periods within today's context
        foreach ($allTodaysPeriods as $period) {
            $periodStart = $period['period_start_time_carbon'];
            $periodEnd = $period['period_end_time_carbon'];

            // 1. Identify current active period
            if ($nowTimestamp >= $periodStart->timestamp && $nowTimestamp <= $periodEnd->timestamp) {
                $currentPeriod = $period;
                $foundCurrent = true;
            }

            // 2. Identify first next upcoming period (after current, or just first future if no current)
            if (!$foundFirstNext) {
                if ($foundCurrent) {
                    if ($periodStart->greaterThan($currentPeriod['period_end_time_carbon'])) {
                        $firstNextPeriod = $period;
                        $foundFirstNext = true;
                    }
                } elseif ($periodStart->isFuture()) {
                    $firstNextPeriod = $period;
                    $foundFirstNext = true;
                }
            }
            // 3. Identify second next upcoming period (after first next)
            elseif ($foundFirstNext && is_null($secondNextPeriod)) {
                // Ensure second period starts after the first next period ends
                if ($periodStart->greaterThan($firstNextPeriod['period_end_time_carbon'])) {
                    $secondNextPeriod = $period;
                }
            }
        }

        // Phase 2: If we still need a next upcoming period (first or second), search in future work days.
        if (is_null($firstNextPeriod) || is_null($secondNextPeriod)) {
            $searchFromDay = $now->copy()->addDay(); // Start searching from tomorrow
            $maxAttempts = 7; // Look up to 7 days ahead

            for ($i = 0; $i < $maxAttempts; $i++) {
                $nextDaySchedule = $weeklySchedule->getDaySchedule(strtolower($searchFromDay->format('l')));

                if ($nextDaySchedule && $nextDaySchedule->enabled) {
                    $dayPeriods = collect($nextDaySchedule->getPeriods())->sortBy('startTime')->all();

                    foreach ($dayPeriods as $futurePeriod) {
                        $futurePeriodDetails = [
                            'status' => 'upcoming_next_day',
                            'day' => ucfirst(strtolower($searchFromDay->format('l'))),
                            'date' => $searchFromDay->format('Y-m-d'),
                            'start_time' => $futurePeriod->startTime,
                            'end_time' => $futurePeriod->endTime,
                            'period_start_time_carbon' => Carbon::createFromTimeString($futurePeriod->startTime, $searchFromDay->timezone)->setDateFrom($searchFromDay),
                            'period_end_time_carbon' => Carbon::createFromTimeString($futurePeriod->endTime, $searchFromDay->timezone)->setDateFrom($searchFromDay)->addDays($futurePeriod->extends_to_next_day ? 1 : 0),
                            'extends_to_next_day' => $futurePeriod->extends_to_next_day,
                        ];

                        if (is_null($firstNextPeriod)) {
                            $firstNextPeriod = $futurePeriodDetails;
                        } elseif (is_null($secondNextPeriod) && $futurePeriodDetails['period_start_time_carbon']->greaterThan($firstNextPeriod['period_end_time_carbon'])) {
                            $secondNextPeriod = $futurePeriodDetails;
                            break 2; // Exit both loops if both are found
                        }
                    }
                    // If we found firstNextPeriod but still need secondNextPeriod and this day is exhausted, continue to next day
                    if ($firstNextPeriod && is_null($secondNextPeriod) && $i < $maxAttempts - 1) {
                         // continue outer loop
                    } elseif ($firstNextPeriod && $secondNextPeriod) {
                         break; // Both found
                    }
                }
                $searchFromDay->addDay(); // Move to the next calendar day
            }
        }

        // Fallback period: If no current or upcoming period is found at all
        $fallbackPeriod = empty($allTodaysPeriods) ? null : $allTodaysPeriods[0];

        return [
            'current_period' => $currentPeriod,
            'first_next_period' => $firstNextPeriod,
            'second_next_period' => $secondNextPeriod,
            'fallback_period' => $fallbackPeriod,
        ];
    }

    private function buildLocationRules(?AttendanceConstraint $constraint, User $user): ?array
    {
        if (!$constraint || !$user->userProfessionalData?->branch_id) return null;
        $userBranchId = (string) $user->userProfessionalData->branch_id;
        $userBranchName = $user->userProfessionalData->branch->name ?? 'Unknown Branch';

        if (!empty($constraint->branch_locations)) {
            $branchData = collect($constraint->branch_locations)->firstWhere('branch_id', $userBranchId);
            if ($branchData) {
                return [
                    'name' => $branchData['name'] ?? $userBranchName,
                    'latitude' => (float)($branchData['latitude'] ?? 0),
                    'longitude' => (float)($branchData['longitude'] ?? 0),
                    'radius' => (int)($branchData['radius'] ?? 0)
                ];
            }
        }

        $locationRules = $constraint->constraint_config['location_rules'] ?? [];
        if (!empty($locationRules['allowed_zones'])) {
            $firstZone = $locationRules['allowed_zones'][0];
            return [
                'name' => $firstZone['name'] ?? $userBranchName,
                'latitude' => (float)($firstZone['latitude'] ?? 0),
                'longitude' => (float)($firstZone['longitude'] ?? 0),
                'radius' => (int)($firstZone['radius'] ?? 0)
            ];
        }
        return null;
    }

    /**
     * Get all constraints applicable to a user for data retrieval purposes.
     * This is a simplified version for finding the winning constraints to display schedules.
     *
     * @param User $user The user to get constraints for
     * @return Collection Collection of applicable constraints
     */
    public function getApplicableConstraintsForDataRetrieval(User $user): Collection
    {
        $cacheKey = sprintf('attendance:constraints:%s:%s', $user->company_id, $user->id);

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($user) {
            return $this->resolveConstraintsFromDb($user);
        });
    }

    private function resolveConstraintsFromDb(User $user): Collection
    {
        $constraint = $user->professionalData?->attendanceConstraint;
        if ($constraint) {
            return collect([$constraint]);
        }

        $userBranchId     = $user->userProfessionalData?->branch?->id;
        $userDepartmentId = $user->userProfessionalData?->department?->id;

        // 1. Check for a Default Constraint on the Branch (single query)
        if ($userBranchId) {
            $defaultConstraint = AttendanceConstraint::whereHas('branches', function ($query) use ($userBranchId) {
                $query->where('management_hierarchies.id', $userBranchId)
                      ->wherePivot('is_default', true);
            })
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->first();

            if ($defaultConstraint) {
                return collect([$defaultConstraint]);
            }
        }

        // 2. If No Default, Find All Other Applicable Constraints by user, department, branch, or global
        return AttendanceConstraint::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where(function ($query) use ($user, $userBranchId, $userDepartmentId) {
                $query->whereJsonContains('user_ids', $user->id)
                ->orWhere(function ($q) use ($user) {
                    $q->when($user->userProfessionalData?->department_id, function ($q2) use ($user) {
                        $q2->whereJsonContains('department_ids', $user->userProfessionalData->department_id);
                    });
                })
                ->orWhere(function ($q) use ($userBranchId) {
                    $q->when($userBranchId, function ($q2) use ($userBranchId) {
                        $q2->whereJsonContains('branch_ids', $userBranchId);
                    });
                })
                ->orWhere(function ($q) {
                    $q->where(fn($sub) => $sub->whereNull('user_ids')->orWhereJsonLength('user_ids', 0))
                      ->where(fn($sub) => $sub->whereNull('department_ids')->orWhereJsonLength('department_ids', 0))
                      ->where(fn($sub) => $sub->whereNull('branch_ids')->orWhereJsonLength('branch_ids', 0));
                });
            })
            ->get();
    }
}

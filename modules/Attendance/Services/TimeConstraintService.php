<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\TimeConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;

/**
 * Service for time-related attendance constraint validations.
 */
class TimeConstraintService extends BaseConstraintService implements TimeConstraintServiceInterface
{
    public function validateTimeConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        switch ($subtype) {
            case AttendanceConstraint::TIME_SHIFT_ENFORCEMENT:
                return $this->validateShiftEnforcement($attendance, $config);

            case AttendanceConstraint::TIME_EARLY_PREVENTION:
                return $this->validateEarlyPrevention($attendance, $config);

            case AttendanceConstraint::TIME_LATE_RESTRICTION:
                return $this->validateLateRestriction($attendance, $config);

            case AttendanceConstraint::TIME_BREAK_LIMITS:
                return $this->validateBreakLimits($attendance, $config);

            case AttendanceConstraint::TIME_OVERTIME_APPROVAL:
                return $this->validateOvertimeApproval($attendance, $config);

            case AttendanceConstraint::TIME_MULTIPLE_PERIODS:
                return $this->validateMultiplePeriods($attendance, $config);

            default:
                return false;
        }
    }

        /**
     * Validates an attendance record against ALL applicable time rules
     * defined within a single configuration.
     *
     * This method checks for schedule compliance, lateness, early departure,
     * break limits, and overtime rules, aggregating any violations found.
     *
     * @param Attendance $attendance The attendance record to validate.
     * @param array $config The 'time_rules' section of the constraint's config.
     * @return bool|array Returns false if no violations are found, or an array of all violations.
     */
    // public function validateTimeConstraint(Attendance $attendance, array $config): bool|array
    // {
    //     $allViolations = [];

    //     // --- Execute All Applicable Validation Checks ---

    //     // 1. Schedule Validation (Work Day, Weekend, Holiday, and Shift Period)
    //     // This is a primary check. If a user tries to clock in on a non-working day,
    //     // it's a significant violation, and we can return it immediately.
    //     $scheduleViolation = $this->validateMultiplePeriods($attendance, $config);

    //     // if (is_array($scheduleViolation)) {
    //     //     // If the violation is for a non-working day, it's critical, so we return it right away.
    //     //     if (in_array($scheduleViolation['details']['reason'], ['Official Holiday', 'Weekend or non-working day'])) {
    //     //         return $scheduleViolation;
    //     //     }
    //     //     $allViolations[] = $scheduleViolation;
    //     // }
    //     // 2. Lateness Check (only on clock-in)
    //     if ($attendance->isDirty('clock_in_time') || !$attendance->exists) {
    //         $latenessViolation = $this->validateLateRestriction($attendance, $config);
    //         if (is_array($latenessViolation)) {
    //             $allViolations[] = $latenessViolation;
    //         }
    //     }
    //     // Only run the following checks if it's a valid work day and time period.
    //     if (empty($allViolations)) {

    //         // Check 2: Early Clock-In Prevention (only on clock-in)
    //         if (isset($config['early_clock_in_rules'])) {
    //             $earlyClockInViolation = $this->validateEarlyClockInPrevention($attendance, $config);
    //             if (is_array($earlyClockInViolation)) {
    //                 $allViolations[] = $earlyClockInViolation;
    //             }
    //         }

    //     // 3. Early Departure Check (only on clock-out)
    //     if ($attendance->isDirty('clock_in_time') && $attendance->clock_in_time) {
    //         $earlyDepartureViolation = $this->validateEarlyPrevention($attendance, $config);
    //         if (is_array($earlyDepartureViolation)) {
    //             $allViolations[] = $earlyDepartureViolation;
    //         }
    //     }

    //     // 4. Break Limits Check
    //     $breakViolation = $this->validateBreakLimits($attendance, $config);
    //     if (is_array($breakViolation)) {
    //         $allViolations[] = $breakViolation;
    //     }

    //     // 5. Overtime Approval Check
    //     // This is most relevant after hours are calculated (e.g., on clock-out).
    //     if ($attendance->overtime_hours > 0) {
    //         $overtimeViolation = $this->validateOvertimeApproval($attendance, $config);
    //         if (is_array($overtimeViolation)) {
    //             $allViolations[] = $overtimeViolation;
    //         }
    //     }
    //     }
    //     // If the violations array is not empty, return the collection of violations.
    //     // Otherwise, return false to indicate success.
    //     return !empty($allViolations) ? $allViolations : false;
    // }


    /**
     * Validates shift enforcement constraints.
     * Ensures that employees clock in and out within their assigned shifts.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateShiftEnforcement(Attendance $attendance, array $config): bool|array
    {
        // Check if shift enforcement is enabled
        $enforceShift = $config['enforce_shift'] ?? false;
        if (!$enforceShift) {
            return false;
        }

        // Get shift times from config
        $shiftStartTime = $config['shift_start_time'] ?? null;
        $shiftEndTime = $config['shift_end_time'] ?? null;

        // If no shift times defined, no violation
        if (!$shiftStartTime || !$shiftEndTime) {
            return false;
        }

        // Use user's timezone for time comparisons
        $timezone = getTimeZoneByRequest() ?? config('app.timezone');
        
        $violations = [];

        // Check clock-in time against shift start time
        if ($attendance->clock_in_time) {
            $clockInTime = Carbon::parse($attendance->clock_in_time)->format('H:i');
            $gracePeriodMinutes = (int)($config['grace_period_minutes'] ?? 0);
            // Calculate the latest allowed clock-in time with grace period
            $shiftStartWithGrace = Carbon::createFromFormat('H:i', $shiftStartTime)
                ->addMinutes($gracePeriodMinutes)
                ->format('H:i');
            if ($clockInTime > $shiftStartWithGrace) {
                $violations[] = [
                    'type' => 'late_clock_in',
                    'message' => "Clock-in time {$clockInTime} is later than allowed shift start time {$shiftStartWithGrace}."
                ];
            }
        }
        // Check clock-out time against shift end time
        if ($attendance->clock_out_time) {
            $clockOutTime = Carbon::parse($attendance->clock_out_time)->format('H:i');
            $earlyDepartureGraceMinutes = (int)($config['early_departure_grace_minutes'] ?? 0);
            // Calculate the earliest allowed clock-out time with grace period
            $shiftEndWithGrace = Carbon::createFromFormat('H:i', $shiftEndTime)
                ->subMinutes($earlyDepartureGraceMinutes)
                ->format('H:i');
            if ($clockOutTime < $shiftEndWithGrace) {
                $violations[] = [
                    'type' => 'early_clock_out',
                    'message' => "Clock-out time {$clockOutTime} is earlier than allowed shift end time {$shiftEndWithGrace}."
                ];
            }
        }
        // If violations found, return constraint violation details
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::TIME_SHIFT_ENFORCEMENT,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Shift enforcement violation detected.',
                'details' => [
                    'shift_start_time' => $shiftStartTime,
                    'shift_end_time' => $shiftEndTime,
                    'violations' => $violations
                ]
            ];
        }

        return false;
    }

    /**
     * Validates early prevention constraints.
     * Ensures that employees do not clock out earlier than allowed.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateEarlyPrevention(Attendance $attendance, array $config): bool|array
    {
        if (!$attendance->clock_in_time) return false;
        // 1. Pre-condition checks.

        // Check if this rule is enabled in the constraint config.
        $rules = $config['early_departure_rules'] ?? [];
        if (!($rules['prevent_early_departure'] ?? false)) {
            return false;
        }

        // 2. Get the user's clock-in time to determine the correct day and shift.
        // clock_in_time is already in correct timezone from request
        $timezone = getTimeZoneByRequest() ?? config('app.timezone');
        $clockInTime = Carbon::parse($attendance->clock_in_time);
        $dayOfWeek = strtolower($clockInTime->format('l'));

        // 3. Find the correct shift period for that day from the main schedule.
        $daySchedule = $config['weekly_schedule'][$dayOfWeek] ?? null;

        // Find the specific period the user clocked into.
        // This is important for multi-period days.
        $activePeriod = null;
        foreach (($daySchedule['periods'] ?? []) as $period) {
            if ($this->isTimeWithinRangeWithGrace($clockInTime, $period)) {
                $activePeriod = $period;
                break;
            }
        }
        // If we can't determine which shift the user was in, we can't check for early departure.
        if (!$activePeriod || !isset($activePeriod['end_time'])) {
            return false;
        }

        // 4. Calculate the earliest allowed departure time.
        $scheduledEndTime = Carbon::createFromTimeString($activePeriod['end_time'])->setDateFrom($clockInTime);
        $clockOutTime = Carbon::parse($attendance->clock_out_time);

        // Handle overnight shifts where end time is the next day.
        if ($scheduledEndTime->isBefore($clockInTime)) {
            $scheduledEndTime->addDay();
        }

        $gracePeriodMinutes = (int)($rules['grace_period_minutes'] ?? 0);
        $earliestAllowedDeparture = $scheduledEndTime->copy()->subMinutes($gracePeriodMinutes);

        // 5. Compare and return a violation if necessary.
        if ($clockOutTime->isBefore($earliestAllowedDeparture)) {
            $minutesEarly = $earliestAllowedDeparture->diffInMinutes($clockOutTime, true);

            return [
                'constraint_type' => AttendanceConstraint::TIME_EARLY_PREVENTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => "Early departure detected. Left {$minutesEarly} minutes before allowed time.",
                'details' => [
                    'clock_out_time' => $clockOutTime->toDateTimeString(),
                    'scheduled_end_time' => $scheduledEndTime->toDateTimeString(),
                    'earliest_allowed_departure' => $earliestAllowedDeparture->toDateTimeString(),
                    'minutes_early' => $minutesEarly
                ]
            ];
        }

        return false;
    }

    /**
     * Validates late restriction constraints.
     * Ensures that employees do not clock in later than allowed.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateLateRestriction(Attendance $attendance, array $config): bool|array
    {
        if (!$attendance->clock_in_time) return false;

        $rules = $config['lateness_rules'] ?? [];
        if (!($rules['prevent_lateness'] ?? false)) return false;

        // clock_in_time is already in correct timezone from request
        $clockInTime = Carbon::parse($attendance->clock_in_time);

        $daySchedule = $config['weekly_schedule'][strtolower($clockInTime->format('l'))] ?? null;
        $firstPeriod = $daySchedule['periods'][0] ?? null;

        if (!$firstPeriod || !isset($firstPeriod['start_time'])) return false;

        $scheduledStartTime = Carbon::createFromTimeString($firstPeriod['start_time'])->setDateFrom($clockInTime);

        // Calculate grace period based on lateness_period and lateness_unit
        $latenessPeriod = (int)($rules['lateness_period'] ?? 0);
        $latenessUnit = $rules['lateness_unit'] ?? 'minute';

        // Convert the lateness period to minutes based on the unit
        $gracePeriodMinutes = $this->convertToMinutes($latenessPeriod, $latenessUnit);

        // If no specific grace period is defined, fall back to grace_period_minutes
        if ($gracePeriodMinutes <= 0) {
            $gracePeriodMinutes = (int)($rules['grace_period_minutes'] ?? 0);
        }

        $latestAllowedArrival = $scheduledStartTime->copy()->addMinutes($gracePeriodMinutes);

        if ($clockInTime->isAfter($latestAllowedArrival)) {
            $minutesLate = $latestAllowedArrival->diffInMinutes($clockInTime, true);
            return [
                'constraint_type' => AttendanceConstraint::TIME_LATE_RESTRICTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => "Late arrival detected. Arrived {$minutesLate} minutes after the grace period.",
                'details' => [
                    'clock_in_time' => $clockInTime->toDateTimeString(),
                    'scheduled_start_time' => $scheduledStartTime->toDateTimeString(),
                    'latest_allowed_arrival' => $latestAllowedArrival->toDateTimeString(),
                    'minutes_late' => $minutesLate,
                    'grace_period' => [
                        'value' => $latenessPeriod,
                        'unit' => $latenessUnit,
                        'minutes_equivalent' => $gracePeriodMinutes
                    ]
                ]
            ];
        }

        return false;
    }

    /**
     * Validates break limits constraints.
     * Ensures that employees do not exceed allowed break durations.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBreakLimits(Attendance $attendance, array $config): bool|array
    {
        // Check if break limits are enforced
        $enforceBreakLimits = $config['enforce_break_limits'] ?? false;
        if (!$enforceBreakLimits) {
            return false;
        }
        // Get break records from attendance
        $breaks = $attendance->breaks ?? [];
        if (empty($breaks)) {
            return false;
        }
        $violations = [];
        $maxBreakDuration = (int)($config['max_break_duration_minutes'] ?? 0);
        $maxBreaksPerDay = (int)($config['max_breaks_per_day'] ?? 0);
        $totalBreakTimeLimit = (int)($config['total_break_time_limit_minutes'] ?? 0);
        // Check number of breaks if limit is set
        if ($maxBreaksPerDay > 0 && count($breaks) > $maxBreaksPerDay) {
            $violations[] = [
                'type' => 'too_many_breaks',
                'message' => "Too many breaks taken: " . count($breaks) . " (limit: {$maxBreaksPerDay})"
            ];
        }
        // Check individual break durations and calculate total
        $totalBreakMinutes = 0;
        foreach ($breaks as $index => $break) {
            if (isset($break['start_time']) && isset($break['end_time'])) {
                $startTime = Carbon::parse($break['start_time']);
                $endTime = Carbon::parse($break['end_time']);
                $durationMinutes = $startTime->diffInMinutes($endTime);

                $totalBreakMinutes += $durationMinutes;

                // Check if individual break exceeds maximum duration
                if ($maxBreakDuration > 0 && $durationMinutes > $maxBreakDuration) {
                    $violations[] = [
                        'type' => 'break_too_long',
                        'message' => "Break #{$index} duration ({$durationMinutes} minutes) exceeds limit ({$maxBreakDuration} minutes)",
                        'break_index' => $index,
                        'duration_minutes' => $durationMinutes
                    ];
                }
            }
        }
        // Check total break time
        if ($totalBreakTimeLimit > 0 && $totalBreakMinutes > $totalBreakTimeLimit) {
            $violations[] = [
                'type' => 'total_break_time_exceeded',
                'message' => "Total break time ({$totalBreakMinutes} minutes) exceeds daily limit ({$totalBreakTimeLimit} minutes)",
                'total_break_minutes' => $totalBreakMinutes
            ];
        }
        // If violations found, return constraint violation details
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::TIME_BREAK_LIMITS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Break limits violation detected.',
                'details' => [
                    'max_break_duration' => $maxBreakDuration,
                    'max_breaks_per_day' => $maxBreaksPerDay,
                    'total_break_time_limit' => $totalBreakTimeLimit,
                    'actual_total_break_time' => $totalBreakMinutes,
                    'violations' => $violations
                ]
            ];
        }

        return false;
    }

    /**
     * Validates overtime approval constraints.
     * Ensures that overtime is properly approved.
     *
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateOvertimeApproval(Attendance $attendance, array $config): bool|array
    {
        // Check if overtime approval is required
        $requiresApproval = $config['requires_approval'] ?? false;
        if (!$requiresApproval) {
            return false;
        }
        // Check if there is overtime
        $overtimeMinutes = $attendance->overtime_minutes ?? 0;
        if ($overtimeMinutes <= 0) {
            return false;
        }
        // Check if overtime is approved
        $isApproved = $attendance->overtime_approved ?? false;
        if ($isApproved) {
            return false;
        }
        // Check if overtime exceeds threshold for approval
        $approvalThresholdMinutes = (int)($config['approval_threshold_minutes'] ?? 0);
        if ($approvalThresholdMinutes > 0 && $overtimeMinutes <= $approvalThresholdMinutes) {
            return false; // No approval needed if under threshold
        }
        // Return violation details
        return [
            'constraint_type' => AttendanceConstraint::TIME_OVERTIME_APPROVAL,
            'severity' => $this->getSeverityFromConfig($config),
            'message' => 'Overtime requires approval.',
            'details' => [
                'overtime_minutes' => $overtimeMinutes,
                'approval_threshold' => $approvalThresholdMinutes,
                'is_approved' => $isApproved
            ]
        ];
    }

    /**
     * Validates multiple periods constraints.
     *
     * This function now performs three main checks in order of priority:
     * 1. Checks if the attendance date is a specified holiday.
     * 2. Checks if the day of the week is a scheduled day off (e.g., weekend).
     * 3. If it's a working day, it ensures the clock-in time is within an allowed work period.
     *
     * @param \Modules\Attendance\Models\Attendance $attendance The attendance record to validate.
     * @param array $config The constraint configuration containing 'weekly_schedule' and 'holidays'.
     * @return bool|array Returns false if no violation is found, or an array with violation details.
     */
    public function validateMultiplePeriods(Attendance $attendance, array $config): bool|array
    {
        // If no weekly schedule is defined, we cannot perform this validation.
        if (!isset($config['weekly_schedule']) || !is_array($config['weekly_schedule'])) {
            // This isn't a violation, just a misconfiguration. Silently pass.
            return false;
        }

        // clock_in_time is already in correct timezone from request
        $clockInTime = Carbon::parse($attendance->clock_in_time);

        // --- FIX 1: CHECK FOR HOLIDAYS FIRST ---
        // Holidays override the regular weekly schedule.
        $holidays = $config['holidays'] ?? [];
        foreach ($holidays as $holiday) {
            if (isset($holiday['date']) && $clockInTime->isSameDay($holiday['date'])) {
                return [
                    'constraint_type' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                    'severity' => $this->getSeverityFromConfig($config),
                    'message' => "Clock-in is not allowed on an official holiday: " . ($holiday['name'] ?? 'Holiday'),
                    'details' => [
                        'date' => $clockInTime->toDateString(),
                        'reason' => 'Official Holiday',
                        'holiday_name' => $holiday['name'] ?? 'Unnamed Holiday'
                    ]
                ];
            }
        }

        // --- FIX 2: CHECK FOR WEEKENDS / SCHEDULED DAYS OFF ---
        $dayOfWeek = strtolower($clockInTime->format('l')); // e.g., "friday"
        $daySchedule = $config['weekly_schedule'][$dayOfWeek] ?? null;

        // If the day is not defined in the schedule OR it is explicitly disabled (enabled: false)
        if (!$daySchedule || !($daySchedule['enabled'] ?? false)) {
            return [
                'constraint_type' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Clock-in is not allowed on a scheduled day off.',
                'details' => [
                    'day_of_week' => $dayOfWeek,
                    'reason' => 'Weekend or non-working day'
                ]
            ];
        }

        // --- ORIGINAL LOGIC (Now only runs for working days) ---

        // A working day must have defined work periods.
        $periods = $daySchedule['periods'] ?? [];

        if (empty($periods)) {
            return [
                'constraint_type' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                'severity' => 'low', // This is likely a configuration error.
                'message' => 'This is a working day, but no work periods have been defined for it.',
                'details' => ['day_of_week' => $dayOfWeek]
            ];
        }

        // Check if the clock-in time falls within any of the defined periods for the day.
        $clockInTimeStr = $clockInTime->format('H:i');
        $inAllowedPeriod = false;
        // First pass: check if clock-in time is within any period (with grace)
        foreach ($periods as $period) {
            if ($this->isTimeWithinRangeWithGrace($clockInTime, $period)) {
                $inAllowedPeriod = true;
                break;
            }
        }

        // If already in an allowed period, no violation
        if ($inAllowedPeriod) {
            return false;
        }

        // Second pass: check early clock-in rules for the NEXT upcoming period only
        $earlyClockInRules = $daySchedule['early_clock_in_rules'] ?? null;
        if ($earlyClockInRules && ($earlyClockInRules['prevent_early_clock_in'] ?? false)) {
            // Find the next upcoming period
            $nextPeriod = null;
            $nextPeriodStart = null;
            
            foreach ($periods as $period) {
                if (!isset($period['start_time']) || !isset($period['end_time'])) {
                    continue;
                }
                
                $startTime = trim($period['start_time']);
                // Validate time format
                if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) {
                    continue;
                }
                
                try {
                    $periodStart = Carbon::createFromFormat('H:i', $startTime)
                        ->setDateFrom($clockInTime);
                    
                    // If period start is after current time, this could be the next period
                    if ($periodStart->gt($clockInTime)) {
                        if ($nextPeriodStart === null || $periodStart->lt($nextPeriodStart)) {
                            $nextPeriod = $period;
                            $nextPeriodStart = $periodStart;
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If there's a next period, check early clock-in rule
            if ($nextPeriod && $nextPeriodStart) {
                $earlyPeriod = $earlyClockInRules['early_period'] ?? 0;
                $earlyUnit = $earlyClockInRules['early_unit'] ?? 'minutes';
                
                $earliestAllowedTime = $nextPeriodStart->copy()->sub($earlyPeriod, $earlyUnit);
                
                // If clock-in time is within the early window, allow it
                if ($clockInTime->gte($earliestAllowedTime) && $clockInTime->lt($nextPeriodStart)) {
                    return false; // Allow early clock-in within grace period
                }
                
                // If clock-in time is before the early window
                if ($clockInTime->lt($earliestAllowedTime)) {
                    return [
                        'constraint_type' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                        'severity' => $this->getSeverityFromConfig($config),
                        'message' => "غير مسموح بتسجيل الحضور قبل {$earlyPeriod} {$earlyUnit} من بداية فترة العمل.",
                        'details' => [
                            'clock_in_time' => $clockInTime->toTimeString(),
                            'earliest_allowed_time' => $earliestAllowedTime->toTimeString(),
                            'period_start' => $nextPeriodStart->toTimeString(),
                            'early_clock_in_rules' => $earlyClockInRules,
                        ]
                    ];
                }
            }
        }

        // If all checks pass, the time is valid.
        return false;
    }

    /**
     * A helper method to check if a time is within a range, including grace periods.
     * You should add this method to your TimeConstraintService or a BaseConstraintService.
     *
     * @param string $time The time to check (e.g., "09:05").
     * @param array $period An array containing 'start_time', 'end_time', and optional grace minutes.
     * @return bool
     */
    private function isTimeWithinRangeWithGrace(Carbon $clockInTime, array $period): bool
    {
        $startTime = $period['start_time'];
        $endTime = $period['end_time'];

        $beforeGraceMinutes = (int)($period['grace_before_minutes'] ?? 0);
        $afterGraceMinutes = (int)($period['grace_after_minutes'] ?? 0);

        $periodStart = Carbon::createFromFormat('H:i', $startTime, $clockInTime->timezone)
            ->setDateFrom($clockInTime)
            ->subMinutes($beforeGraceMinutes);

        $periodEnd = Carbon::createFromFormat('H:i', $endTime, $clockInTime->timezone)
            ->setDateFrom($clockInTime)
            ->addMinutes($afterGraceMinutes);

        if ($periodEnd->lessThan($periodStart)) {
            // Overnight shift
            $periodEnd->addDay();
        }

        return $clockInTime->between($periodStart, $periodEnd);
    }

    /**
     * Converts a time value from the specified unit to minutes.
     *
     * @param int $value The time value to convert
     * @param string $unit The unit of the time value ('minute', 'hour', or 'day')
     * @return int The equivalent time value in minutes
     */
    private function convertToMinutes(int $value, string $unit): int
    {
        switch (strtolower($unit)) {
            case 'hour':
                return $value * 60;
            case 'day':
                return $value * 24 * 60;
            case 'minute':
            default:
                return $value;
        }
    }
     /**
     * Validates that an employee does not clock in too early.
     *
     * This checks the clock-in time against the scheduled start time of the relevant
     * shift period, minus any allowed grace period for clocking in early.
     *
     * @param Attendance $attendance The attendance record to validate.
     * @param array $config The 'time_rules' section of the constraint's config.
     * @return bool|array Returns false if compliant, or a violation array if the clock-in is too early.
     */
    private function validateEarlyClockInPrevention(Attendance $attendance, array $config): bool|array
    {
        // 1. Check if the rule is enabled.
        $rules = $config['early_clock_in_rules'] ?? [];
        if (!($rules['prevent_early_clock_in'] ?? false) || !$attendance->clock_in_time) {
            return false;
        }

        // clock_in_time is already in correct timezone from request
        $clockInTimeLocal = Carbon::parse($attendance->clock_in_time);

        $dayOfWeek = strtolower($clockInTimeLocal->format('l'));
        $daySchedule = $config['weekly_schedule'][$dayOfWeek] ?? null;
        $firstPeriod = $daySchedule['periods'][0] ?? null;

        if (!$firstPeriod || !isset($firstPeriod['start_time'])) {
            return false;
        }

        // Ensure start time is properly formatted
        $startTime = trim($firstPeriod['start_time']);

        // Validate time format
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $startTime)) {
            \Illuminate\Support\Facades\Log::error('Invalid period start time format in validateEarlyClockInPrevention', [
                'start_time' => $startTime,
                'attendance_id' => $attendance->id,
                'day_of_week' => $dayOfWeek
            ]);
            return false;
        }

        try {
            // Calculate the earliest allowed clock-in time based on the scheduled start time
            $scheduledStartTime = Carbon::createFromTimeString($startTime)
                                      ->setDateFrom($clockInTimeLocal);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create Carbon object from period start time', [
                'start_time' => $startTime,
                'error' => $e->getMessage(),
                'attendance_id' => $attendance->id
            ]);
            return false;
        }

        // Calculate grace period based on early_period and early_unit
        $earlyPeriod = (int)($rules['early_period'] ?? 0);
        $earlyUnit = $rules['early_unit'] ?? 'minute';

        // Convert the early period to minutes based on the unit
        $gracePeriodMinutes = $this->convertToMinutes($earlyPeriod, $earlyUnit);

        // If no specific grace period is defined, fall back to grace_period_minutes
        if ($gracePeriodMinutes <= 0) {
            $gracePeriodMinutes = (int)($rules['grace_period_minutes'] ?? 0);
        }

        $earliestAllowedClockIn = $scheduledStartTime->copy()->subMinutes($gracePeriodMinutes);

        // Compare the clock-in time with the earliest allowed time
        if ($clockInTimeLocal->isBefore($earliestAllowedClockIn)) {
            $minutesEarly = $earliestAllowedClockIn->diffInMinutes($clockInTimeLocal, true);

            return [
                'constraint_type' => 'early_clock_in_prevention',
                'severity' => $this->getSeverityFromConfig($config),
                'message' => "Clock-in is too early. You can clock in from {$earliestAllowedClockIn->format('H:i')}.",
                'details' => [
                    'clock_in_time' => $clockInTimeLocal->toDateTimeString(),
                    'scheduled_start_time' => $scheduledStartTime->toDateTimeString(),
                    'earliest_allowed_clock_in' => $earliestAllowedClockIn->toDateTimeString(),
                    'minutes_early' => $minutesEarly,
                    'grace_period' => [
                        'value' => $earlyPeriod,
                        'unit' => $earlyUnit,
                        'minutes_equivalent' => $gracePeriodMinutes
                    ]
                ]
            ];
        }

        return false;
    }
}

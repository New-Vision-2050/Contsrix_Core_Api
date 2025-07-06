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
    /**
     * Validate time constraints for attendance.
     * This is a dispatcher method that handles different types of time constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
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
        if (!$attendance->clock_out_time) {
            return false;
        }
        
        $clockOutTime = Carbon::parse($attendance->clock_out_time);
        $scheduledEndTime = null;
        
        if (isset($config['scheduled_end_time'])) {
            $scheduledEndTime = Carbon::parse($config['scheduled_end_time']);
        } elseif (isset($config['work_hours']) && is_numeric($config['work_hours'])) {
            $workHoursInMinutes = (float)$config['work_hours'] * 60;
            $scheduledEndTime = Carbon::parse($attendance->clock_in_time)->addMinutes($workHoursInMinutes);
        } else {
            return false;
        }
        
        $gracePeriodMinutes = isset($config['grace_period_minutes']) ? (int)$config['grace_period_minutes'] : 0;
        $earliestAllowedDeparture = $scheduledEndTime->copy()->subMinutes($gracePeriodMinutes);
        
        if ($clockOutTime->lt($earliestAllowedDeparture)) {
            $minutesEarly = $earliestAllowedDeparture->diffInMinutes($clockOutTime);
            
            return [
                'constraint_type' => AttendanceConstraint::TIME_EARLY_PREVENTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => "Early departure detected. Left {$minutesEarly} minutes before scheduled end time.",
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
        if (!$attendance->clock_in_time) {
            return false;
        }
        
        $clockInTime = Carbon::parse($attendance->clock_in_time);
        $scheduledStartTime = null;
        
        if (isset($config['scheduled_start_time'])) {
            $scheduledStartTime = Carbon::parse($config['scheduled_start_time']);
        } else {
            return false;
        }
        
        $gracePeriodMinutes = isset($config['grace_period_minutes']) ? (int)$config['grace_period_minutes'] : 0;
        $latestAllowedArrival = $scheduledStartTime->copy()->addMinutes($gracePeriodMinutes);
        
        if ($clockInTime->gt($latestAllowedArrival)) {
            $minutesLate = $clockInTime->diffInMinutes($latestAllowedArrival);
            
            return [
                'constraint_type' => AttendanceConstraint::TIME_LATE_RESTRICTION,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => "Late arrival detected. Arrived {$minutesLate} minutes after allowed start time.",
                'details' => [
                    'clock_in_time' => $clockInTime->toDateTimeString(),
                    'scheduled_start_time' => $scheduledStartTime->toDateTimeString(),
                    'latest_allowed_arrival' => $latestAllowedArrival->toDateTimeString(),
                    'minutes_late' => $minutesLate
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
     * Ensures that clock in/out times fall within allowed periods.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateMultiplePeriods(Attendance $attendance, array $config): bool|array
    {
        // Check if weekly schedule is defined
        if (!isset($config['weekly_schedule']) || !is_array($config['weekly_schedule'])) {
            return false;
        }
        
        // Get day of week for attendance
        $clockInTime = Carbon::parse($attendance->clock_in_time);
        $dayOfWeek = strtolower($clockInTime->format('l')); // e.g., "monday"
        
        // Check if this day has defined periods
        if (!isset($config['weekly_schedule'][$dayOfWeek]) || 
            !isset($config['weekly_schedule'][$dayOfWeek]['enabled']) ||
            !$config['weekly_schedule'][$dayOfWeek]['enabled'] ||
            !isset($config['weekly_schedule'][$dayOfWeek]['periods']) ||
            !is_array($config['weekly_schedule'][$dayOfWeek]['periods'])) {
            return false;
        }
        
        // Get periods for this day
        $periods = $config['weekly_schedule'][$dayOfWeek]['periods'];
        if (empty($periods)) {
            return false;
        }
        
        // Format clock-in time as HH:MM for comparison
        $clockInTimeStr = $clockInTime->format('H:i');
        
        // Check if clock-in time falls within any allowed period
        $inAllowedPeriod = false;
        $allowedPeriods = [];
        
        foreach ($periods as $period) {
            if (!isset($period['start_time']) || !isset($period['end_time'])) {
                continue;
            }
            
            $startTime = $period['start_time'];
            $endTime = $period['end_time'];
            $spansNextDay = $period['spans_next_day'] ?? false;
            
            // Get grace periods if defined
            $beforeGraceMinutes = (int)($period['grace_before_minutes'] ?? 0);
            $afterGraceMinutes = (int)($period['grace_after_minutes'] ?? 0);
            
            // Adjust start and end times with grace periods
            $effectiveStartTime = Carbon::createFromFormat('H:i', $startTime)
                ->subMinutes($beforeGraceMinutes)
                ->format('H:i');
            
            $effectiveEndTime = Carbon::createFromFormat('H:i', $endTime)
                ->addMinutes($afterGraceMinutes)
                ->format('H:i');
            
            // Check if time falls within this period (considering overnight spans)
            if ($this->isTimeWithinRange($clockInTimeStr, $effectiveStartTime, $effectiveEndTime)) {
                $inAllowedPeriod = true;
                break;
            }
            
            $allowedPeriods[] = [
                'name' => $period['name'] ?? "Period",
                'start_time' => $startTime,
                'end_time' => $endTime,
                'effective_start' => $effectiveStartTime,
                'effective_end' => $effectiveEndTime
            ];
        }
        
        // If not in any allowed period, return violation
        if (!$inAllowedPeriod) {
            return [
                'constraint_type' => AttendanceConstraint::TIME_MULTIPLE_PERIODS,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Clock-in time is outside of all allowed periods for this day.',
                'details' => [
                    'day_of_week' => $dayOfWeek,
                    'clock_in_time' => $clockInTimeStr,
                    'allowed_periods' => $allowedPeriods
                ]
            ];
        }
        
        return false;
    }
}

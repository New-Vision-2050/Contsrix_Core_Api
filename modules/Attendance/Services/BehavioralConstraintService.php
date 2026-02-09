<?php

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Contracts\BehavioralConstraintServiceInterface;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Repositories\AttendanceRepository;

/**
 * Service for behavioral attendance constraint validations.
 */
class BehavioralConstraintService extends BaseConstraintService implements BehavioralConstraintServiceInterface
{
    protected $attendanceRepository;

    public function __construct(AttendanceRepository $attendanceRepository)
    {
        $this->attendanceRepository = $attendanceRepository;
    }

    /**
     * Validate behavioral constraints for attendance.
     * This is a dispatcher method that handles different types of behavioral constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBehavioralConstraint(Attendance $attendance, array $config): bool|array
    {
        // Get constraint subtype
        $subtype = $config['subtype'] ?? '';
        
        switch ($subtype) {
            case AttendanceConstraint::BEHAVIORAL_FREQUENCY:
                return $this->validateFrequencyPattern($attendance, $config);
                
            case AttendanceConstraint::BEHAVIORAL_PATTERN:
                return $this->validateBehavioralPattern($attendance, $config);
                
            case AttendanceConstraint::BEHAVIORAL_CONSISTENCY:
                return $this->validateConsistencyPattern($attendance, $config);
                
            case AttendanceConstraint::BEHAVIORAL_ANOMALY:
                return $this->validateAnomalyDetection($attendance, $config);
                
            case AttendanceConstraint::BEHAVIORAL_HABIT:
                return $this->validateHabitPattern($attendance, $config);
                
            default:
                return false;
        }
    }
    
    /**
     * Validate consecutive limit constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateConsecutiveLimit(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $currentDate = Carbon::parse($attendance->attendance_date);
        
        // Get the consecutive day limit
        $maxConsecutiveDays = $config['max_consecutive_days'] ?? 0;
        if ($maxConsecutiveDays <= 0) {
            return false; // No limit set, no violation
        }
        
        // Get user's attendance history for the past few weeks
        $pastAttendances = $this->attendanceRepository->getUserAttendancesBeforeDate(
            $user->id,
            $currentDate,
            $maxConsecutiveDays + 10 // Get enough history to check consecutive days
        );
        
        if (empty($pastAttendances)) {
            return false; // No history to check
        }
        
        // Count consecutive days worked without breaks
        $consecutiveDays = 1; // Current day counts as 1
        $previousDate = $currentDate->copy()->subDay();
        
        foreach ($pastAttendances as $pastAttendance) {
            $attendanceDate = Carbon::parse($pastAttendance->attendance_date);
            
            if ($attendanceDate->eq($previousDate)) {
                $consecutiveDays++;
                $previousDate->subDay();
            } else {
                // Break in consecutive days
                break;
            }
        }
        
        if ($consecutiveDays > $maxConsecutiveDays) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_FREQUENCY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Exceeded maximum consecutive days allowed to work.',
                'details' => [
                    'user_id' => $user->id,
                    'consecutive_days' => $consecutiveDays,
                    'max_allowed' => $maxConsecutiveDays
                ]
            ];
        }
        
        return false;
    }
    
    /**
     * Validate weekly hours constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateWeeklyHours(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $currentDate = Carbon::parse($attendance->attendance_date);
        
        // Get the weekly hour limit
        $maxWeeklyHours = $config['max_weekly_hours'] ?? 0;
        if ($maxWeeklyHours <= 0) {
            return false; // No limit set, no violation
        }
        
        // Get the start of the week for this attendance
        $weekStartDate = $currentDate->copy()->startOfWeek();
        $weekEndDate = $currentDate->copy()->endOfWeek();
        
        // Get all attendances for this user in the current week
        $weeklyAttendances = $this->attendanceRepository->getUserAttendancesBetweenDates(
            $user->id,
            $weekStartDate,
            $weekEndDate
        );
        
        // Calculate total hours worked this week
        $totalMinutes = 0;
        foreach ($weeklyAttendances as $weeklyAttendance) {
            if ($weeklyAttendance->id !== $attendance->id && $weeklyAttendance->clock_in_time && $weeklyAttendance->clock_out_time) {
                $clockIn = Carbon::parse($weeklyAttendance->clock_in_time);
                $clockOut = Carbon::parse($weeklyAttendance->clock_out_time);
                
                $totalMinutes += $clockIn->diffInMinutes($clockOut);
            }
        }
        
        // Add current attendance duration or estimated duration
        $timezone = $attendance->timezone ?? getTimeZoneBranchByRequest() ?? config('app.timezone');
        $clockIn = Carbon::parse($attendance->clock_in_time, $timezone);
        $clockOut = $attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time, $timezone) : Carbon::now($timezone);
        $totalMinutes += $clockIn->diffInMinutes($clockOut);
        
        $totalHours = $totalMinutes / 60;
        
        if ($totalHours > $maxWeeklyHours) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_FREQUENCY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Exceeded maximum weekly hours allowed to work.',
                'details' => [
                    'user_id' => $user->id,
                    'total_hours' => round($totalHours, 2),
                    'max_allowed' => $maxWeeklyHours,
                    'week_start' => $weekStartDate->toDateString(),
                    'week_end' => $weekEndDate->toDateString()
                ]
            ];
        }
        
        return false;
    }
    
    /**
     * Validate rest periods constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateRestPeriods(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $currentDateTime = Carbon::parse($attendance->clock_in_time);
        
        // Get the minimum rest period between shifts in hours
        $minRestHours = $config['min_rest_hours'] ?? 0;
        if ($minRestHours <= 0) {
            return false; // No minimum rest period set, no violation
        }
        
        // Find the most recent previous attendance record
        $previousAttendance = $this->attendanceRepository->getLastAttendanceBeforeDate(
            $user->id,
            $currentDateTime->copy()->subHours($minRestHours * 2) // Look back far enough
        );
        
        if (!$previousAttendance || !$previousAttendance->clock_out_time) {
            return false; // No previous attendance or incomplete record
        }
        
        // Calculate rest period between previous clock-out and current clock-in
        $previousClockOut = Carbon::parse($previousAttendance->clock_out_time);
        $restPeriodHours = $previousClockOut->diffInMinutes($currentDateTime) / 60;
        
        if ($restPeriodHours < $minRestHours) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_CONSISTENCY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Insufficient rest period between shifts.',
                'details' => [
                    'user_id' => $user->id,
                    'rest_period_hours' => round($restPeriodHours, 2),
                    'min_required_hours' => $minRestHours,
                    'previous_clock_out' => $previousClockOut->format('Y-m-d H:i:s'),
                    'current_clock_in' => $currentDateTime->format('Y-m-d H:i:s')
                ]
            ];
        }
        
        return false;
    }
    
    /**
     * Validate holiday work constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateHolidayWork(Attendance $attendance, array $config): bool|array
    {
        $attendanceDate = Carbon::parse($attendance->attendance_date);
        
        // Check if holiday work is restricted
        $holidayWorkRestricted = $config['holiday_work_restricted'] ?? false;
        if (!$holidayWorkRestricted) {
            return false; // Holiday work is allowed, no violation
        }
        
        // Check if the attendance date is a holiday
        $holidays = $config['holidays'] ?? [];
        $isHoliday = false;
        $holidayName = '';
        
        foreach ($holidays as $holiday) {
            $holidayDate = Carbon::parse($holiday['date']);
            
            if ($attendanceDate->isSameDay($holidayDate)) {
                $isHoliday = true;
                $holidayName = $holiday['name'] ?? 'Holiday';
                break;
            }
        }
        
        // Also check if date is a weekend and weekend restrictions apply
        $weekendRestricted = $config['weekend_restricted'] ?? false;
        $isWeekend = $attendanceDate->isWeekend();
        
        if (($isHoliday && $holidayWorkRestricted) || ($isWeekend && $weekendRestricted)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_PATTERN,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => $isHoliday ? "Work on holiday '{$holidayName}' is restricted." : 'Work on weekend is restricted.',
                'details' => [
                    'attendance_date' => $attendanceDate->toDateString(),
                    'is_holiday' => $isHoliday,
                    'holiday_name' => $holidayName,
                    'is_weekend' => $isWeekend,
                    'day_of_week' => $attendanceDate->format('l')
                ]
            ];
        }
        
        return false;
    }
    
    /**
     * Validate pattern monitoring constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validatePatternMonitoring(Attendance $attendance, array $config): bool|array
    {
        // This method can leverage the existing validateBehavioralPattern and validateAnomalyDetection
        // methods as they're already handling pattern monitoring
        
        // First check for behavioral patterns
        $behavioralResult = $this->validateBehavioralPattern($attendance, $config);
        if ($behavioralResult !== false) {
            return $behavioralResult;
        }
        
        // Then check for anomalies
        $anomalyResult = $this->validateAnomalyDetection($attendance, $config);
        if ($anomalyResult !== false) {
            return $anomalyResult;
        }
        
        return false;
    }

    /**
     * Validate frequency pattern constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateFrequencyPattern(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $violations = [];
        
        // Check maximum clock-ins per day
        if (isset($config['max_clock_ins_per_day'])) {
            $maxClockIns = (int)$config['max_clock_ins_per_day'];
            $today = Carbon::parse($attendance->clock_in_time)->startOfDay();
            
            $todayClockIns = $this->attendanceRepository->getUserAttendanceForDate($user->id, $today);
            $clockInCount = $todayClockIns->count() + 1; // +1 for current attendance
            
            if ($clockInCount > $maxClockIns) {
                $violations[] = [
                    'type' => 'excessive_daily_clock_ins',
                    'message' => "Exceeded maximum clock-ins per day: {$clockInCount}/{$maxClockIns}",
                    'current_count' => $clockInCount,
                    'max_allowed' => $maxClockIns
                ];
            }
        }
        
        // Check minimum time between clock-ins
        if (isset($config['min_minutes_between_clock_ins'])) {
            $minMinutes = (int)$config['min_minutes_between_clock_ins'];
            $lastAttendance = $this->attendanceRepository->getLastAttendance($user->id);
            
            if ($lastAttendance && $lastAttendance->clock_out_time) {
                $timeDiff = Carbon::parse($lastAttendance->clock_out_time)
                    ->diffInMinutes(Carbon::parse($attendance->clock_in_time));
                
                if ($timeDiff < $minMinutes) {
                    $violations[] = [
                        'type' => 'too_frequent_clock_ins',
                        'message' => "Clock-in too soon after last clock-out: {$timeDiff} minutes (minimum: {$minMinutes})",
                        'time_diff_minutes' => $timeDiff,
                        'min_required_minutes' => $minMinutes,
                        'last_clock_out' => $lastAttendance->clock_out_time
                    ];
                }
            }
        }
        
        // Check weekly attendance frequency
        if (isset($config['max_attendance_days_per_week'])) {
            $maxDays = (int)$config['max_attendance_days_per_week'];
            $weekStart = Carbon::parse($attendance->clock_in_time)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $weeklyAttendance = $this->attendanceRepository->getUserAttendanceBetweenDates(
                $user->id, $weekStart, $weekEnd
            );
            
            $attendanceDays = $weeklyAttendance->groupBy(function ($item) {
                return Carbon::parse($item->clock_in_time)->format('Y-m-d');
            })->count() + 1; // +1 for current day
            
            if ($attendanceDays > $maxDays) {
                $violations[] = [
                    'type' => 'excessive_weekly_attendance',
                    'message' => "Exceeded maximum attendance days per week: {$attendanceDays}/{$maxDays}",
                    'current_days' => $attendanceDays,
                    'max_allowed_days' => $maxDays,
                    'week_start' => $weekStart->format('Y-m-d'),
                    'week_end' => $weekEnd->format('Y-m-d')
                ];
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_FREQUENCY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Attendance frequency violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate behavioral pattern constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateBehavioralPattern(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $violations = [];
        
        // Check for unusual clock-in times
        if (isset($config['detect_unusual_times']) && $config['detect_unusual_times']) {
            $clockInHour = Carbon::parse($attendance->clock_in_time)->hour;
            $unusualHours = $config['unusual_hours'] ?? [0, 1, 2, 3, 4, 5, 22, 23];
            
            if (in_array($clockInHour, $unusualHours)) {
                $violations[] = [
                    'type' => 'unusual_clock_in_time',
                    'message' => "Clock-in at unusual hour: {$clockInHour}:00",
                    'clock_in_hour' => $clockInHour,
                    'unusual_hours' => $unusualHours
                ];
            }
        }
        
        // Check for weekend work patterns
        if (isset($config['detect_weekend_patterns']) && $config['detect_weekend_patterns']) {
            $dayOfWeek = Carbon::parse($attendance->clock_in_time)->dayOfWeek;
            
            if (in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])) {
                $weekendWorkAllowed = $config['weekend_work_allowed'] ?? false;
                
                if (!$weekendWorkAllowed) {
                    $violations[] = [
                        'type' => 'weekend_work_not_allowed',
                        'message' => 'Weekend work is not allowed',
                        'day_of_week' => Carbon::parse($attendance->clock_in_time)->format('l')
                    ];
                } else {
                    // Check if user has excessive weekend work
                    $maxWeekendDaysPerMonth = $config['max_weekend_days_per_month'] ?? null;
                    
                    if ($maxWeekendDaysPerMonth) {
                        $monthStart = Carbon::parse($attendance->clock_in_time)->startOfMonth();
                        $monthEnd = $monthStart->copy()->endOfMonth();
                        
                        $monthlyWeekendAttendance = $this->attendanceRepository
                            ->getUserAttendanceBetweenDates($user->id, $monthStart, $monthEnd)
                            ->filter(function ($item) {
                                $dayOfWeek = Carbon::parse($item->clock_in_time)->dayOfWeek;
                                return in_array($dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
                            });
                        
                        $weekendDays = $monthlyWeekendAttendance->groupBy(function ($item) {
                            return Carbon::parse($item->clock_in_time)->format('Y-m-d');
                        })->count() + 1; // +1 for current day
                        
                        if ($weekendDays > $maxWeekendDaysPerMonth) {
                            $violations[] = [
                                'type' => 'excessive_weekend_work',
                                'message' => "Exceeded maximum weekend days per month: {$weekendDays}/{$maxWeekendDaysPerMonth}",
                                'current_weekend_days' => $weekendDays,
                                'max_allowed_weekend_days' => $maxWeekendDaysPerMonth
                            ];
                        }
                    }
                }
            }
        }
        
        // Check for holiday work patterns
        if (isset($config['detect_holiday_patterns']) && $config['detect_holiday_patterns']) {
            $clockInDate = Carbon::parse($attendance->clock_in_time)->format('Y-m-d');
            $holidays = $config['holidays'] ?? [];
            
            if (in_array($clockInDate, $holidays)) {
                $holidayWorkAllowed = $config['holiday_work_allowed'] ?? false;
                
                if (!$holidayWorkAllowed) {
                    $violations[] = [
                        'type' => 'holiday_work_not_allowed',
                        'message' => 'Holiday work is not allowed',
                        'holiday_date' => $clockInDate
                    ];
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_PATTERN,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Behavioral pattern violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate consistency pattern constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateConsistencyPattern(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $violations = [];
        
        // Check clock-in time consistency
        if (isset($config['check_clock_in_consistency']) && $config['check_clock_in_consistency']) {
            $consistencyDays = (int)($config['consistency_check_days'] ?? 7);
            $maxVarianceMinutes = (int)($config['max_variance_minutes'] ?? 30);
            
            $recentAttendance = $this->attendanceRepository->getRecentAttendance($user->id, $consistencyDays);
            
            if ($recentAttendance->count() >= 3) {
                $clockInTimes = $recentAttendance->map(function ($item) {
                    return $this->timeToMinutes(Carbon::parse($item->clock_in_time)->format('H:i'));
                })->toArray();
                
                $currentClockInMinutes = $this->timeToMinutes(
                    Carbon::parse($attendance->clock_in_time)->format('H:i')
                );
                
                $averageClockIn = array_sum($clockInTimes) / count($clockInTimes);
                $variance = abs($currentClockInMinutes - $averageClockIn);
                
                if ($variance > $maxVarianceMinutes) {
                    $violations[] = [
                        'type' => 'inconsistent_clock_in_time',
                        'message' => "Clock-in time is inconsistent with recent pattern: {$variance} minutes variance (max: {$maxVarianceMinutes})",
                        'variance_minutes' => $variance,
                        'max_variance_minutes' => $maxVarianceMinutes,
                        'average_clock_in_time' => sprintf('%02d:%02d', floor($averageClockIn / 60), $averageClockIn % 60),
                        'current_clock_in_time' => Carbon::parse($attendance->clock_in_time)->format('H:i')
                    ];
                }
            }
        }
        
        // Check location consistency
        if (isset($config['check_location_consistency']) && $config['check_location_consistency']) {
            $currentLocation = $attendance->location ?? null;
            
            if ($currentLocation) {
                $recentAttendance = $this->attendanceRepository->getRecentAttendance($user->id, 7);
                $recentLocations = $recentAttendance->pluck('location')->filter()->toArray();
                
                if (count($recentLocations) >= 3) {
                    $locationConsistency = $this->calculateLocationConsistency($currentLocation, $recentLocations);
                    $minConsistencyPercent = (float)($config['min_location_consistency_percent'] ?? 70);
                    
                    if ($locationConsistency < $minConsistencyPercent) {
                        $violations[] = [
                            'type' => 'inconsistent_location',
                            'message' => "Location is inconsistent with recent pattern: {$locationConsistency}% consistency (min: {$minConsistencyPercent}%)",
                            'location_consistency_percent' => $locationConsistency,
                            'min_consistency_percent' => $minConsistencyPercent
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_CONSISTENCY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Consistency pattern violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate anomaly detection constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateAnomalyDetection(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $violations = [];
        
        // Check for rapid successive clock-ins
        if (isset($config['detect_rapid_clock_ins']) && $config['detect_rapid_clock_ins']) {
            $rapidThresholdMinutes = (int)($config['rapid_threshold_minutes'] ?? 5);
            $lastAttendance = $this->attendanceRepository->getLastAttendance($user->id);
            
            if ($lastAttendance) {
                $timeDiff = Carbon::parse($lastAttendance->clock_in_time)
                    ->diffInMinutes(Carbon::parse($attendance->clock_in_time));
                
                if ($timeDiff < $rapidThresholdMinutes) {
                    $violations[] = [
                        'type' => 'rapid_successive_clock_ins',
                        'message' => "Rapid successive clock-ins detected: {$timeDiff} minutes apart (threshold: {$rapidThresholdMinutes})",
                        'time_diff_minutes' => $timeDiff,
                        'threshold_minutes' => $rapidThresholdMinutes
                    ];
                }
            }
        }
        
        // Check for location jumping
        if (isset($config['detect_location_jumping']) && $config['detect_location_jumping']) {
            $currentLocation = $attendance->location ?? null;
            $lastAttendance = $this->attendanceRepository->getLastAttendance($user->id);
            
            if ($currentLocation && $lastAttendance && $lastAttendance->location) {
                $distance = $this->calculateDistance(
                    $lastAttendance->location['latitude'] ?? 0,
                    $lastAttendance->location['longitude'] ?? 0,
                    $currentLocation['latitude'] ?? 0,
                    $currentLocation['longitude'] ?? 0
                );
                
                $maxDistanceKm = (float)($config['max_location_jump_km'] ?? 100);
                $minTimeHours = (float)($config['min_time_for_jump_hours'] ?? 1);
                
                $timeDiffHours = Carbon::parse($lastAttendance->clock_in_time)
                    ->diffInHours(Carbon::parse($attendance->clock_in_time));
                
                if ($distance > $maxDistanceKm && $timeDiffHours < $minTimeHours) {
                    $violations[] = [
                        'type' => 'impossible_location_jump',
                        'message' => "Impossible location jump detected: {$distance} km in {$timeDiffHours} hours",
                        'distance_km' => $distance,
                        'time_hours' => $timeDiffHours,
                        'max_distance_km' => $maxDistanceKm,
                        'min_time_hours' => $minTimeHours
                    ];
                }
            }
        }
        
        // Check for unusual work duration patterns
        if (isset($config['detect_unusual_duration']) && $config['detect_unusual_duration']) {
            $recentAttendance = $this->attendanceRepository->getRecentAttendance($user->id, 14);
            $completedAttendance = $recentAttendance->filter(function ($item) {
                return $item->clock_out_time !== null;
            });
            
            if ($completedAttendance->count() >= 5) {
                $durations = $completedAttendance->map(function ($item) {
                    return Carbon::parse($item->clock_in_time)
                        ->diffInHours(Carbon::parse($item->clock_out_time));
                })->toArray();
                
                $averageDuration = array_sum($durations) / count($durations);
                $standardDeviation = $this->calculateStandardDeviation($durations, $averageDuration);
                $deviationThreshold = (float)($config['duration_deviation_threshold'] ?? 2.0);
                
                // This is a placeholder - we can't calculate current duration without clock_out_time
                // This would be better checked during clock-out
                if ($standardDeviation > $deviationThreshold) {
                    $violations[] = [
                        'type' => 'unusual_duration_pattern',
                        'message' => "Unusual work duration pattern detected",
                        'average_duration_hours' => $averageDuration,
                        'standard_deviation' => $standardDeviation,
                        'threshold' => $deviationThreshold
                    ];
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_ANOMALY,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Behavioral anomaly detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Validate habit pattern constraints.
     * 
     * @param Attendance $attendance The attendance record to validate
     * @param array $config The constraint configuration
     * @return bool|array Returns false if no violation, or violation details if constraint is violated
     */
    public function validateHabitPattern(Attendance $attendance, array $config): bool|array
    {
        $user = $attendance->user;
        $violations = [];
        
        // Check for break in attendance streak
        if (isset($config['check_attendance_streak']) && $config['check_attendance_streak']) {
            $minStreakDays = (int)($config['min_streak_days'] ?? 5);
            $streakBreakThresholdDays = (int)($config['streak_break_threshold_days'] ?? 2);
            
            $lastAttendance = $this->attendanceRepository->getLastAttendance($user->id);
            
            if ($lastAttendance) {
                $daysSinceLastAttendance = Carbon::parse($lastAttendance->clock_in_time)
                    ->diffInDays(Carbon::parse($attendance->clock_in_time));
                
                if ($daysSinceLastAttendance > $streakBreakThresholdDays) {
                    $violations[] = [
                        'type' => 'attendance_streak_broken',
                        'message' => "Attendance streak broken: {$daysSinceLastAttendance} days since last attendance (threshold: {$streakBreakThresholdDays})",
                        'days_since_last_attendance' => $daysSinceLastAttendance,
                        'threshold_days' => $streakBreakThresholdDays
                    ];
                }
            }
        }
        
        // Check for deviation from established routine
        if (isset($config['check_routine_deviation']) && $config['check_routine_deviation']) {
            $routineCheckDays = (int)($config['routine_check_days'] ?? 30);
            $recentAttendance = $this->attendanceRepository->getRecentAttendance($user->id, $routineCheckDays);
            
            if ($recentAttendance->count() >= 10) {
                $dayOfWeek = Carbon::parse($attendance->clock_in_time)->dayOfWeek;
                $sameDayAttendance = $recentAttendance->filter(function ($item) use ($dayOfWeek) {
                    return Carbon::parse($item->clock_in_time)->dayOfWeek === $dayOfWeek;
                });
                
                if ($sameDayAttendance->count() >= 3) {
                    $sameDayTimes = $sameDayAttendance->map(function ($item) {
                        return $this->timeToMinutes(Carbon::parse($item->clock_in_time)->format('H:i'));
                    })->toArray();
                    
                    $currentTimeMinutes = $this->timeToMinutes(
                        Carbon::parse($attendance->clock_in_time)->format('H:i')
                    );
                    
                    $averageTime = array_sum($sameDayTimes) / count($sameDayTimes);
                    $deviation = abs($currentTimeMinutes - $averageTime);
                    $maxDeviationMinutes = (int)($config['max_routine_deviation_minutes'] ?? 60);
                    
                    if ($deviation > $maxDeviationMinutes) {
                        $violations[] = [
                            'type' => 'routine_deviation',
                            'message' => "Significant deviation from established routine: {$deviation} minutes (max: {$maxDeviationMinutes})",
                            'deviation_minutes' => $deviation,
                            'max_deviation_minutes' => $maxDeviationMinutes,
                            'day_of_week' => Carbon::parse($attendance->clock_in_time)->format('l')
                        ];
                    }
                }
            }
        }
        
        if (!empty($violations)) {
            return [
                'constraint_type' => AttendanceConstraint::BEHAVIORAL_HABIT,
                'severity' => $this->getSeverityFromConfig($config),
                'message' => 'Habit pattern violation detected.',
                'details' => [
                    'violations' => $violations
                ]
            ];
        }
        
        return false;
    }

    /**
     * Calculate location consistency percentage.
     * 
     * @param array $currentLocation Current location
     * @param array $recentLocations Array of recent locations
     * @return float Consistency percentage
     */
    private function calculateLocationConsistency(array $currentLocation, array $recentLocations): float
    {
        if (empty($recentLocations)) {
            return 100.0;
        }
        
        $similarLocations = 0;
        $toleranceMeters = 100; // 100 meters tolerance
        
        foreach ($recentLocations as $location) {
            if (!isset($location['latitude'], $location['longitude'])) {
                continue;
            }
            
            $distance = $this->calculateDistance(
                $currentLocation['latitude'] ?? 0,
                $currentLocation['longitude'] ?? 0,
                $location['latitude'],
                $location['longitude']
            ) * 1000; // Convert to meters
            
            if ($distance <= $toleranceMeters) {
                $similarLocations++;
            }
        }
        
        return ($similarLocations / count($recentLocations)) * 100;
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

    /**
     * Calculate standard deviation of an array of values.
     * 
     * @param array $values Array of numeric values
     * @param float $mean Mean of the values
     * @return float Standard deviation
     */
    private function calculateStandardDeviation(array $values, float $mean): float
    {
        if (count($values) <= 1) {
            return 0.0;
        }
        
        $sumSquaredDifferences = array_sum(array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values));
        
        return sqrt($sumSquaredDifferences / (count($values) - 1));
    }
}

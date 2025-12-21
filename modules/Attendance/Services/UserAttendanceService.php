<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserAttendanceService
{
    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get work rules/constraints for a user
     *
     * @param UuidInterface|string $userId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     * @throws ModelNotFoundException
     */
    public function getUserConstraints(UuidInterface|string $userId, ?string $date = null): array
    {
        $user = User::findOrFail($userId);
        $targetDate = $date ?? Carbon::now()->format('Y-m-d');
        $dateCarbon = Carbon::parse($targetDate);

        // Always pass the resolved target date to ensure consistency
        $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $targetDate);
        $attendances = $this->getAttendancesForDate($user, $dateCarbon);

        if (isset($workRules['all_work_periods']) && is_array($workRules['all_work_periods'])) {
            $workRules['all_work_periods'] = $this->enhancePeriodsWithAttendance(
                $workRules['all_work_periods'],
                $attendances,
                $dateCarbon
            );
        }

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'date' => $targetDate,
            'work_rules' => $this->filterWorkRules($workRules),
        ];
    }

    /**
     * Check if user is clocked in
     *
     * @param UuidInterface|string $userId
     * @return array
     * @throws ModelNotFoundException|AttendanceException
     */
    public function checkClockInStatus(UuidInterface|string $userId): array
    {
        $user = User::findOrFail($userId);
        $attendance = $this->getCurrentAttendanceSafely($userId);

        return [
            'user_id' => (string) $user->id,
            'user_name' => $user->name,
            'is_clocked_in' => $attendance?->isActive() ?? false,
            'is_on_break' => $attendance?->isOnBreak() ?? false,
            'attendance_id' => $attendance ? (string) $attendance->id : null,
            'clock_in_time' => $attendance?->clock_in_time?->format('Y-m-d H:i:s'),
            'status' => $attendance?->status ?? 'not_clocked_in',
        ];
    }

    /**
     * Get attendance records for a user on a specific date
     *
     * @param User $user
     * @param Carbon $date
     * @return Collection
     */
    private function getAttendancesForDate(User $user, Carbon $date): Collection
    {
        return Attendance::where('user_id', $user->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('start_time', $date->format('Y-m-d'))
                    ->orWhereDate('clock_in_time', $date->format('Y-m-d'));
            })
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Enhance periods with attendance records
     *
     * @param array $periods
     * @param Collection $attendances
     * @param Carbon $date
     * @return array
     */
    private function enhancePeriodsWithAttendance(array $periods, Collection $attendances, Carbon $date): array
    {
        return array_map(function ($period) use ($attendances, $date) {
            $periodStart = $this->parsePeriodTime($period, 'start', $date);
            $periodEnd = $this->parsePeriodTime($period, 'end', $date);

            $totalWorkHours = $this->calculatePeriodWorkHours($periodStart, $periodEnd);
            $periodAttendances = $this->findAttendancesInPeriod($attendances, $periodStart, $periodEnd);
            // Evaluate activity using the period's timezone so local day windows are respected
            $nowInPeriodTz = Carbon::now($periodStart->getTimezone());
            $isActive = $this->isPeriodActive($periodStart, $periodEnd, $nowInPeriodTz);

            return $this->mergePeriodData($period, $totalWorkHours, $periodAttendances, $isActive);
        }, $periods);
    }

    /**
     * Parse period time from period data
     *
     * @param array $period
     * @param string $type 'start' or 'end'
     * @param Carbon $date
     * @return Carbon
     */
    private function parsePeriodTime(array $period, string $type, Carbon $date): Carbon
    {
        $carbonKey = "period_{$type}_time_carbon";
        $timeKey = "{$type}_time";

        if (isset($period[$carbonKey])) {
            $time = $period[$carbonKey];
            return $time instanceof Carbon ? $time : Carbon::parse($time);
        }

        $time = Carbon::parse($date->format('Y-m-d') . ' ' . $period[$timeKey]);

        if ($type === 'end' && ($period['extends_to_next_day'] ?? false)) {
            $time->addDay();
        }

        return $time;
    }

    /**
     * Find attendances that fall within a period
     *
     * @param Collection $attendances
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @return array
     */
    private function findAttendancesInPeriod(Collection $attendances, Carbon $periodStart, Carbon $periodEnd): array
    {
        return $attendances
            ->filter(function ($attendance) use ($periodStart, $periodEnd) {
                // Normalize attendance times with awareness of timezone column if present
                $attendanceTz = $attendance->timezone ?? $periodStart->getTimezone();

                // clock-in based match (actual event)
                $clockInCarbon = null;
                if ($attendance->clock_in_time) {
                    $clockInCarbon = $attendance->clock_in_time instanceof Carbon
                        ? $attendance->clock_in_time->copy()->setTimezone($attendanceTz)
                        : Carbon::parse($attendance->clock_in_time, $attendanceTz);
                        
                    $clockInInPeriodTz = $clockInCarbon->copy()->setTimezone($periodStart->getTimezone());
                    if ($clockInInPeriodTz->between($periodStart, $periodEnd, true)) {
                        return true;
                    }
                }
                $attStart = $this->getAttendanceTime($attendance, 'start');
                $attEnd = $this->getAttendanceTime($attendance, 'end');
                if ($attStart) {
                    $attStart = $attStart instanceof Carbon ? $attStart->copy() : Carbon::parse((string) $attStart, $attendanceTz);
                    $attStart->setTimezone($periodStart->getTimezone());
                }
                if ($attEnd) {
                    $attEnd = $attEnd instanceof Carbon ? $attEnd->copy() : Carbon::parse((string) $attEnd, $attendanceTz);
                    $attEnd->setTimezone($periodStart->getTimezone());
                } else {
                    $attEnd = Carbon::now($periodStart->getTimezone());
                }

                if ($attStart) {
                    // Overlap if attendanceStart <= periodEnd AND attendanceEnd >= periodStart
                    return $attStart->lessThanOrEqualTo($periodEnd) && $attEnd->greaterThanOrEqualTo($periodStart);
                }

                return false;
            })
            ->map(fn($attendance) => $this->formatAttendanceForPeriod($attendance))
            ->values()
            ->toArray();
    }

    /**
     * Get attendance time (start or end)
     *
     * @param Attendance $attendance
     * @param string $type 'start' or 'end'
     * @return Carbon|null
     */
    private function getAttendanceTime(Attendance $attendance, string $type): ?Carbon
    {
        $time = $type === 'start'
            ? ($attendance->start_time ?? $attendance->clock_in_time)
            : ($attendance->end_time ?? $attendance->clock_out_time);

        if (!$time) {
            return null;
        }

        return $time instanceof Carbon ? $time : Carbon::parse($time);
    }

    /**
     * Format attendance data for period response
     *
     * @param Attendance $attendance
     * @return array
     */
    private function formatAttendanceForPeriod(Attendance $attendance): array
    {
        $startTime = $this->getAttendanceTime($attendance, 'start');
        $endTime = $this->getAttendanceTime($attendance, 'end');
        
        // Get clock_in_time and clock_out_time
        $clockInTime = null;
        $clockOutTime = null;
        $clockInCarbon = null;
        $clockOutCarbon = null;
        
        if ($attendance->clock_in_time) {
            $clockInCarbon = $attendance->clock_in_time instanceof Carbon 
                ? $attendance->clock_in_time 
                : Carbon::parse($attendance->clock_in_time);
            $clockInTime = $clockInCarbon->format('H:i');
        }
        
        if ($attendance->clock_out_time) {
            $clockOutCarbon = $attendance->clock_out_time instanceof Carbon 
                ? $attendance->clock_out_time 
                : Carbon::parse($attendance->clock_out_time);
            $clockOutTime = $clockOutCarbon->format('H:i');
        }

        // Calculate total hours present
        $totalHoursPresent = 0;
        if ($clockInCarbon) {
            if ($clockOutCarbon) {
                // If clocked out, calculate difference
                $totalHoursPresent = round($clockInCarbon->diffInMinutes($clockOutCarbon) / 60, 2);
            } else {
                // If still active, calculate from clock_in to now
                $totalHoursPresent = round($clockInCarbon->diffInMinutes(Carbon::now()) / 60, 2);
            }
        }

        return [
            'status' => $attendance->status ?? 'scheduled',
            'date' => $startTime?->format('Y-m-d') ?? ($clockInTime ? $clockInCarbon->format('Y-m-d') : null),
            'start_time' => $startTime?->format('H:i'),
            'end_time' => $endTime?->format('H:i'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'total_hours_present' => $totalHoursPresent,
        ];
    }

    /**
     * Merge period data with calculated values
     *
     * @param array $period
     * @param float $totalWorkHours
     * @param array $attendance
     * @param bool $isActive
     * @return array
     */
    private function mergePeriodData(array $period, float $totalWorkHours, array $attendance, bool $isActive): array
    {
        $cleanedPeriod = $period;
        unset($cleanedPeriod['period_start_time_carbon'], $cleanedPeriod['period_end_time_carbon']);
        
        // Calculate total hours present from all attendance records in this period
        $totalHoursPresent = 0;
        foreach ($attendance as $att) {
            $totalHoursPresent += $att['total_hours_present'] ?? 0;
        }
        
        // Determine if user can clock in
        // Can clock in if period is active AND no active attendance exists
        $hasActiveAttendance = collect($attendance)->contains(function ($att) {
            return $att['status'] === 'active';
        });
        
        $canClockIn = $isActive && !$hasActiveAttendance;
        
        return array_merge($cleanedPeriod, [
            'total_work_hours' => $totalWorkHours,
            'is_active' => $isActive,
            'total_hours_present' => round($totalHoursPresent, 2),
            'can_clock_in' => $canClockIn,
            'attendance' => $attendance,
        ]);
    }

    /**
     * Filter work rules to only include required fields
     *
     * @param array $workRules
     * @return array
     */
    private function filterWorkRules(array $workRules): array
    {
        $locationWork = $workRules['location_work'] ?? null;
        
        return [
            'day_status' => $workRules['day_status'] ?? null,
            'day_name' => $workRules['day_name'] ?? null,
            'is_holiday' => $workRules['is_holiday'] ?? false,
            'reason' => $workRules['reason'] ?? null,
            'all_work_periods' => $workRules['all_work_periods'] ?? [],
            'location_work' => $locationWork ? [
                'name' => $locationWork['name'] ?? null,
                'latitude' => $locationWork['latitude'] ?? null,
                'longitude' => $locationWork['longitude'] ?? null,
                'radius' => $locationWork['radius'] ?? null,
            ] : null,
        ];
    }

    /**
     * Calculate work hours for a period
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return float
     */
    private function calculatePeriodWorkHours(Carbon $start, Carbon $end): float
    {
        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * Check if period is currently active (current time is within period range)
     *
     * @param Carbon $periodStart
     * @param Carbon $periodEnd
     * @param Carbon $now
     * @return bool
     */
    private function isPeriodActive(Carbon $periodStart, Carbon $periodEnd, Carbon $now): bool
    {
        return $now->between($periodStart, $periodEnd, true);
    }

    /**
     * Get current attendance safely, handling exceptions
     *
     * @param UuidInterface|string $userId
     * @return Attendance|null
     */
    private function getCurrentAttendanceSafely(UuidInterface|string $userId): ?Attendance
    {
        try {
            $userIdUuid = is_string($userId) ? Uuid::fromString($userId) : $userId;
            return $this->attendanceService->getCurrentAttendance($userIdUuid);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user attendance history grouped by date with periods
     *
     * @param UuidInterface|string $userId
     * @param int|null $month
     * @param int|null $year
     * @param int $page
     * @param int $perPage
     * @return array
     * @throws ModelNotFoundException
     */
    public function getUserAttendanceHistory(
        UuidInterface|string $userId,
        ?int $month = null,
        ?int $year = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $user = User::findOrFail($userId);
        $now = Carbon::now();
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;

        // Build query for attendance records
        $query = Attendance::where('user_id', $user->id)
            ->where(function ($q) use ($currentYear, $currentMonth) {
                $q->where(function ($subQ) use ($currentYear, $currentMonth) {
                    $subQ->whereNotNull('start_time')
                        ->whereYear('start_time', $currentYear);
                    if ($currentMonth) {
                        $subQ->whereMonth('start_time', $currentMonth);
                    }
                })->orWhere(function ($subQ) use ($currentYear, $currentMonth) {
                    $subQ->whereNull('start_time')
                        ->whereNotNull('clock_in_time')
                        ->whereYear('clock_in_time', $currentYear);
                    if ($currentMonth) {
                        $subQ->whereMonth('clock_in_time', $currentMonth);
                    }
                });
            })
            ->orderByRaw('COALESCE(start_time, clock_in_time) DESC')
            ->get();

        // Group attendances by date
        $groupedByDate = $query->groupBy(function ($attendance) {
            $date = $attendance->start_time ?? $attendance->clock_in_time;
            return Carbon::parse($date)->format('Y-m-d');
        });

        $result = [];
        foreach ($groupedByDate as $dateString => $attendances) {
            $dateCarbon = Carbon::parse($dateString);
            
            // Get work rules for this date
            $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $dateString);
            $periods = $workRules['all_work_periods'] ?? [];

            // Match attendances to periods
            $periodsWithAttendance = $this->matchAttendancesToPeriods($periods, $attendances, $dateCarbon);

            // Determine day status
            $dayStatus = $this->determineDayStatus($attendances);

            // Get day name in Arabic
            $dayName = $this->getDayNameArabic($dateCarbon);

            $result[] = [
                'date' => $dateString,
                'day_name' => $dayName,
                'status' => $dayStatus,
                'periods_count' => count($periodsWithAttendance),
                'periods' => $periodsWithAttendance,
            ];
        }

        // Sort by date descending
        usort($result, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        // Paginate
        $total = count($result);
        $offset = ($page - 1) * $perPage;
        $paginatedResult = array_slice($result, $offset, $perPage);

        return [
            'data' => $paginatedResult,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'next_page' => $page < ceil($total / $perPage) ? $page + 1 : null,
                'result_count' => count($paginatedResult),
            ],
        ];
    }

    /**
     * Match attendances to work periods
     *
     * @param array $periods
     * @param Collection $attendances
     * @param Carbon $date
     * @return array
     */
    private function matchAttendancesToPeriods(array $periods, Collection $attendances, Carbon $date): array
    {
        $periodsWithAttendance = [];

        foreach ($periods as $index => $period) {
            $periodStart = $this->parsePeriodTime($period, 'start', $date);
            $periodEnd = $this->parsePeriodTime($period, 'end', $date);

            // Find attendances that fall within this period
            $periodAttendances = $attendances->filter(function ($attendance) use ($periodStart, $periodEnd) {
                $clockInTime = $attendance->clock_in_time;
                if (!$clockInTime) {
                    return false;
                }
                $clockInCarbon = $clockInTime instanceof Carbon ? $clockInTime : Carbon::parse($clockInTime);
                return $clockInCarbon->between($periodStart, $periodEnd, true);
            });

            if ($periodAttendances->isEmpty()) {
                continue;
            }

            // Sort attendances by clock_in_time to get the latest one
            $sortedAttendances = $periodAttendances->sortByDesc(function ($attendance) {
                if (!$attendance->clock_in_time) {
                    return 0;
                }
                $clockInCarbon = $attendance->clock_in_time instanceof Carbon 
                    ? $attendance->clock_in_time 
                    : Carbon::parse($attendance->clock_in_time);
                return $clockInCarbon->timestamp;
            })->values();

            // Get the latest attendance record (last clock_in)
            $latestAttendance = $sortedAttendances->first();

            // Get clock_in_time and clock_out_time from the latest attendance
            $clockInTime = null;
            $clockOutTime = null;
            $clockInLocation = null;
            $clockOutLocation = null;

            if ($latestAttendance->clock_in_time) {
                $clockInCarbon = $latestAttendance->clock_in_time instanceof Carbon 
                    ? $latestAttendance->clock_in_time 
                    : Carbon::parse($latestAttendance->clock_in_time);
                $clockInTime = $clockInCarbon->format('H:i');
                $clockInLocation = $latestAttendance->clock_in_location;
            }

            // Get clock_out_time from the same attendance record (last clock_in)
            if ($latestAttendance->clock_out_time) {
                $clockOutCarbon = $latestAttendance->clock_out_time instanceof Carbon 
                    ? $latestAttendance->clock_out_time 
                    : Carbon::parse($latestAttendance->clock_out_time);
                $clockOutTime = $clockOutCarbon->format('H:i');
                $clockOutLocation = $latestAttendance->clock_out_location;
            }

            // Calculate total_work_hours from all attendances in the period
            $totalWorkHours = 0;
            foreach ($periodAttendances as $attendance) {
                // Use total_work_hours from attendance record if available and > 0
                if (isset($attendance->total_work_hours) && $attendance->total_work_hours > 0) {
                    $totalWorkHours += (float) $attendance->total_work_hours;
                } elseif ($attendance->clock_in_time) {
                    // If total_work_hours is 0 or not set, calculate from clock_in to clock_out
                    $clockInCarbon = $attendance->clock_in_time instanceof Carbon 
                        ? $attendance->clock_in_time 
                        : Carbon::parse($attendance->clock_in_time);
                    
                    if ($attendance->clock_out_time) {
                        $clockOutCarbon = $attendance->clock_out_time instanceof Carbon 
                            ? $attendance->clock_out_time 
                            : Carbon::parse($attendance->clock_out_time);
                        $workMinutes = $clockInCarbon->diffInMinutes($clockOutCarbon);
                    } else {
                        // If no clock_out, calculate from clock_in to now
                        $workMinutes = $clockInCarbon->diffInMinutes(Carbon::now());
                    }
                    $totalWorkHours += round($workMinutes / 60, 2);
                }
            }

            // Aggregate delay and overtime from all attendances in period
            $totalDelayMinutes = 0;
            $totalOvertimeHours = 0;
            foreach ($periodAttendances as $attendance) {
                $totalDelayMinutes += (int) ($attendance->late_minutes ?? 0);
                $totalOvertimeHours += (float) ($attendance->overtime_hours ?? 0);
            }

            $periodsWithAttendance[] = [
                'clock_in_time' => $clockInTime,
                'clock_out_time' => $clockOutTime, // Will be null if no clock_out in last clock_in
                'work_hours' => $this->formatHoursToTime($totalWorkHours),
                'delay_hours' => $this->formatMinutesToTime($totalDelayMinutes),
                'overtime_hours' => $this->formatHoursToTime($totalOvertimeHours),
                'clock_in_location' => $clockInLocation,
                'clock_out_location' => $clockOutLocation,
            ];
        }

        return $periodsWithAttendance;
    }

    /**
     * Determine day status based on attendances
     *
     * @param Collection $attendances
     * @return string
     */
    private function determineDayStatus(Collection $attendances): string
    {
        if ($attendances->isEmpty()) {
            return 'غائب';
        }

        $hasCompleted = $attendances->contains(function ($attendance) {
            return $attendance->clock_out_time !== null;
        });

        $hasActive = $attendances->contains(function ($attendance) {
            return $attendance->clock_out_time === null && $attendance->status === 'active';
        });

        if ($hasCompleted && !$hasActive) {
            return 'تم الخروج';
        }

        if ($hasActive) {
            return 'نشط';
        }

        return 'تم الخروج';
    }

    /**
     * Get day name in Arabic
     *
     * @param Carbon $date
     * @return string
     */
    private function getDayNameArabic(Carbon $date): string
    {
        $dayNames = [
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت',
        ];

        $englishDayName = $date->format('l');
        return $dayNames[$englishDayName] ?? $englishDayName;
    }

    /**
     * Format hours to H:i format
     *
     * @param float $hours
     * @return string
     */
    private function formatHoursToTime(float $hours): string
    {
        $totalMinutes = (int) round($hours * 60);
        $h = intval($totalMinutes / 60);
        $m = $totalMinutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Format minutes to H:i format
     *
     * @param int $minutes
     * @return string
     */
    private function formatMinutesToTime(int $minutes): string
    {
        $h = intval($minutes / 60);
        $m = $minutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
}


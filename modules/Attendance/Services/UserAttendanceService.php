<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    // =============================================================================
    // Public API
    // =============================================================================

    /**
     * Get work rules/constraints for a user
     *
     * @param UuidInterface|string $userId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     */
    public function getUserConstraints(UuidInterface|string $userId, ?string $date = null): array
    {
        $user = User::findOrFail($userId);
        
        $timezone = $this->getTimezone();
        
        $targetDate = $date ?? $this->now()->format('Y-m-d');
        $dateCarbon = $this->parseDateTime($targetDate, $timezone);

        // Always pass the resolved target date to ensure consistency
        $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $targetDate);
        $attendances = $this->getAttendancesForDate($user, $dateCarbon);

        if (isset($workRules['all_work_periods']) && is_array($workRules['all_work_periods'])) {
            $earlyClockInRules = $workRules['early_clock_in_rules'] ?? null;
            $workRules['all_work_periods'] = $this->enhancePeriodsWithAttendance(
                $workRules['all_work_periods'],
                $attendances,
                $dateCarbon,
                is_array($earlyClockInRules) ? $earlyClockInRules : []
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
            'clock_in_time' => $attendance?->clock_in_time ? $this->toCarbon($attendance->clock_in_time)->format('Y-m-d H:i:s') : null,
            'status' => $attendance?->status ?? 'not_clocked_in',
        ];
    }

    // =============================================================================
    // Period & Attendance Enhancement
    // =============================================================================

    /**
     * Get attendance records for a user on a specific date
     *
     * @param User $user
     * @param Carbon $date
     * @return Collection
     */
    private function getAttendancesForDate(User $user, Carbon $date): Collection
    {
        // Ensure we're using the correct timezone for date comparison
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $dateInTz = $date->copy()->setTimezone($timezone);
        
        // Convert date range to UTC for database query (database stores times in UTC)
        $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
        $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');
        
        return Attendance::where('user_id', $user->id)
            ->where(function ($query) use ($dayStartUtc, $dayEndUtc) {
                $query->whereBetween('start_time', [$dayStartUtc, $dayEndUtc])
                    ->orWhereBetween('clock_in_time', [$dayStartUtc, $dayEndUtc]);
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
    private function enhancePeriodsWithAttendance(array $periods, Collection $attendances, Carbon $date, array $earlyClockInRules): array
    {
        return array_map(function ($period) use ($attendances, $date, $earlyClockInRules) {
            $periodStart = $this->parsePeriodTime($period, 'start', $date);
            $periodEnd = $this->parsePeriodTime($period, 'end', $date);

            $totalWorkHours = $this->calculatePeriodWorkHours($periodStart, $periodEnd);
            $periodAttendances = $this->findAttendancesInPeriod($attendances, $periodStart, $periodEnd);
            
            $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
            $now = Carbon::now($timezone);
            
            // Active when now is inside period, or inside early clock-in window (e.g. start 16:00, early 30 min → active from 15:30)
            $isActive = $this->isPeriodActiveIncludingEarly($periodStart, $periodEnd, $now, $earlyClockInRules);
  
            return $this->mergePeriodData($period, $totalWorkHours, $periodAttendances, $isActive, $earlyClockInRules);
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

        // Get consistent timezone
        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');

        if (isset($period[$carbonKey])) {
            $time = $period[$carbonKey];
            $carbonTime = $time instanceof Carbon ? $time : Carbon::parse($time);
            return $carbonTime->setTimezone($timezone);
        }

        // Parse time with consistent timezone
        $time = Carbon::parse($date->format('Y-m-d') . ' ' . $period[$timeKey], $timezone);

        if ($type === 'end' && ($period['extends_to_next_day'] ?? false)) {
            $time->addDay();
        }

        return $time;
    }

    /**
     * Find attendances that fall within a period
     * Only matches attendance if clock_in_time is within the period boundaries
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
                // Only match by clock_in_time - attendance belongs to the period where clock_in happened
                if (!$attendance->clock_in_time) {
                    return false;
                }
                
                $attendanceTz = $attendance->timezone ?? $periodStart->getTimezone();
                $clockInCarbon = $attendance->clock_in_time instanceof Carbon
                    ? $attendance->clock_in_time->copy()->setTimezone($attendanceTz)
                    : Carbon::parse($attendance->clock_in_time, $attendanceTz);
                    
                $clockInInPeriodTz = $clockInCarbon->copy()->setTimezone($periodStart->getTimezone());
                return $clockInInPeriodTz->between($periodStart, $periodEnd, true);
            })
            ->map(fn($attendance) => $this->formatAttendanceForPeriod($attendance, $periodStart, $periodEnd))
            ->values()
            ->toArray();
    }

    /**
     * Parse datetime value to Carbon instance.
     */
    private function toCarbon(mixed $value, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? $this->getTimezone();
        return $value instanceof Carbon ? $value->copy()->setTimezone($tz) : Carbon::parse($value, $tz);
    }

    /**
     * Extract clock-in/out times and Carbon instances from attendance.
     *
     * @return array{clock_in_carbon: Carbon|null, clock_out_carbon: Carbon|null, clock_in_time: string|null, clock_out_time: string|null}
     */
    private function extractAttendanceClockData(Attendance $attendance): array
    {
        $clockInCarbon = $attendance->clock_in_time ? $this->toCarbon($attendance->clock_in_time) : null;
        $clockOutCarbon = $attendance->clock_out_time ? $this->toCarbon($attendance->clock_out_time) : null;

        return [
            'clock_in_carbon' => $clockInCarbon,
            'clock_out_carbon' => $clockOutCarbon,
            'clock_in_time' => $clockInCarbon?->format('H:i'),
            'clock_out_time' => $clockOutCarbon?->format('H:i'),
        ];
    }

    /**
     * Format attendance data for period response.
     */
    private function formatAttendanceForPeriod(Attendance $attendance, Carbon $periodStart, Carbon $periodEnd): array
    {
        $clock = $this->extractAttendanceClockData($attendance);
        $clockInCarbon = $clock['clock_in_carbon'];
        $clockOutCarbon = $clock['clock_out_carbon'];

        $totalHoursPresent = 0;
        if ($clockInCarbon) {
            $endRef = $clockOutCarbon ?? $this->now();
            $totalHoursPresent = round(max(0, $clockInCarbon->diffInMinutes($endRef, true)) / 60, 2);
        }

        return [
            'status' => $attendance->status ?? 'scheduled',
            'date' => $clockInCarbon?->format('Y-m-d') ?? $periodStart->format('Y-m-d'),
            'start_time' => $periodStart->format('H:i'),
            'end_time' => $periodEnd->format('H:i'),
            'clock_in_time' => $clock['clock_in_time'],
            'clock_out_time' => $clock['clock_out_time'],
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
    private function mergePeriodData(array $period, float $totalWorkHours, array $attendance, bool $isActive, array $earlyClockInRules): array
    {
        $cleanedPeriod = $period;
        unset($cleanedPeriod['period_start_time_carbon'], $cleanedPeriod['period_end_time_carbon']);
        
        $totalHoursPresent = 0;
        foreach ($attendance as $att) {
            $totalHoursPresent += $att['total_hours_present'] ?? 0;
        }
        
        $hasActiveAttendance = collect($attendance)->contains(function ($att) {
            return $att['status'] === 'active';
        });
        
        $getCurrentAttendance = $this->attendanceService->getCurrentAttendance(auth()->user()->id);
        $canClockIn = $isActive && !$hasActiveAttendance && (bool) !$getCurrentAttendance;


        $effectiveIsActive = $isActive || $hasActiveAttendance;

        return array_merge($cleanedPeriod, [
            'total_work_hours' => $totalWorkHours,
            'is_active' => $effectiveIsActive,
            'total_hours_present' => round($totalHoursPresent, 2),
            'can_clock_in' => $canClockIn,
            'can_clock_out' => (bool) $getCurrentAttendance,
            'early_clock_in_rules' => $this->buildEarlyClockInRulesForResponse($earlyClockInRules),
            'attendance' => $attendance,
        ]);
    }

    /**
     * Build early clock-in rules for API response.
     */
    private function buildEarlyClockInRulesForResponse(array $earlyClockInRules): array
    {
        return [
            'prevent_early_clock_in' => (bool) ($earlyClockInRules['prevent_early_clock_in'] ?? false),
            'early_period' => (int) ($earlyClockInRules['early_period'] ?? 0),
            'early_unit' => $earlyClockInRules['early_unit'] ?? 'minutes',
        ];
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
     * Period is active if now is inside the period or inside the early clock-in window.
     * E.g. start 16:00, early 30 min → active from 15:30 so can_clock_in and is_active true at 15:30.
     */
    private function isPeriodActiveIncludingEarly(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $now,
        array $earlyClockInRules
    ): bool {
        if ($now->between($periodStart, $periodEnd, true)) {
            return true;
        }
        $earlyPeriod = (int) ($earlyClockInRules['early_period'] ?? 0);
        $earlyUnit = (string) ($earlyClockInRules['early_unit'] ?? 'minutes');
        if ($earlyPeriod <= 0 || $earlyUnit === '') {
            return false;
        }
        // Normalize "minute" to "minutes" for Carbon
        if (strtolower($earlyUnit) === 'minute') {
            $earlyUnit = 'minutes';
        }
        $earliestAllowed = $periodStart->copy()->sub($earlyPeriod, $earlyUnit);

        return $now->between($earliestAllowed, $periodEnd, true);
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

    // =============================================================================
    // Attendance History
    // =============================================================================

    /**
     * Get user attendance history grouped by date with periods
     *
     * @param UuidInterface|string $userId
     * @param int|null $month
     * @param int|null $year
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserAttendanceHistory(
        UuidInterface|string $userId,
        ?int $month = null,
        ?int $year = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $user = User::findOrFail($userId);
        $timezone = $this->getTimezone();
        $now = $this->now();
        $currentYear = $year ?? $now->year;
        $currentMonth = $month ?? $now->month;

        // Build date range for the full requested month (including future auto-generated
        // absent/holiday records that may already exist for days later in the month).
        $rangeStart = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        $rangeEnd = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->endOfMonth();

        // Convert to UTC for database query (database stores times in UTC)
        $rangeStartUtc = $rangeStart->copy()->setTimezone('UTC');
        $rangeEndUtc = $rangeEnd->copy()->setTimezone('UTC');

        // Single query to get all attendances for the month window (sargable ranges)
        $allAttendances = Attendance::where('user_id', $user->id)
            ->where(function ($q) use ($rangeStartUtc, $rangeEndUtc) {
                $q->whereBetween('start_time', [$rangeStartUtc, $rangeEndUtc])
                  ->orWhere(function ($q2) use ($rangeStartUtc, $rangeEndUtc) {
                      $q2->whereNull('start_time')
                         ->whereBetween('clock_in_time', [$rangeStartUtc, $rangeEndUtc]);
                  });
            })
            ->orderByRaw('COALESCE(start_time, clock_in_time) DESC')
            ->get();

        // Group attendances by date in the request timezone
        $attendancesByDate = $allAttendances->groupBy(function ($attendance) use ($timezone) {
            $dateField = $attendance->start_time ?? $attendance->clock_in_time;
            if (!$dateField) {
                return null;
            }
            return $this->parseDateTime($dateField, $timezone)->toDateString();
        })->filter(fn($group, $key) => $key !== null);

        // Get unique dates sorted descending
        $allDates = $attendancesByDate->keys()->sort()->reverse()->values();
        $totalDates = $allDates->count();
        $lastPage = (int) ceil($totalDates / $perPage);
        $offset = ($page - 1) * $perPage;

        // Paginate dates
        $paginatedDates = $allDates->slice($offset, $perPage);

        $result = [];
        foreach ($paginatedDates as $dateString) {
            $dateCarbon = $this->parseDateTime($dateString, $timezone);
            $attendances = $attendancesByDate->get($dateString, collect());

            // Build simple periods directly from attendances without heavy constraint lookups
            $periodsWithAttendance = $this->buildPeriodsFromAttendances($attendances);

            // Determine day status from attendance
            $dayStatus = $this->determineDayStatus($attendances);
            $dayName = $this->getDayNameArabic($dateCarbon);

            $result[] = [
                'date' => $dateString,
                'day_name' => $dayName,
                'status' => $dayStatus,
                'is_late' => (int) $attendances->contains(fn($a) => (bool) $a->is_late),
                'is_absent' => (int) $attendances->contains(fn($a) => (bool) $a->is_absent),
                'is_holiday' => (int) $attendances->contains(fn($a) => (bool) $a->is_holiday),
                'periods_count' => count($periodsWithAttendance),
                'periods' => $periodsWithAttendance,
            ];
        }

        return [
            'data' => $result,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $totalDates,
                'last_page' => $lastPage,
                'next_page' => $page < $lastPage ? $page + 1 : null,
                'result_count' => count($result),
            ],
        ];
    }

    /**
     * Build periods data directly from attendances without heavy constraint lookups.
     */
    private function buildPeriodsFromAttendances(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        $periods = [];
        foreach ($attendances as $attendance) {
            $clock = $this->extractAttendanceClockData($attendance);
            $clockInCarbon = $clock['clock_in_carbon'];
            $clockOutCarbon = $clock['clock_out_carbon'];

            $totalWorkHours = $this->calculateAttendanceWorkHours($attendance, $clockInCarbon, $clockOutCarbon);

            $periods[] = [
                'clock_in_time' => $clock['clock_in_time'],
                'clock_out_time' => $clock['clock_out_time'],
                'work_hours' => $this->formatHoursToTime($totalWorkHours),
                'delay_hours' => $this->formatMinutesToTime((int) ($attendance->late_minutes ?? 0)),
                'overtime_hours' => $this->formatHoursToTime((float) ($attendance->overtime_hours ?? 0)),
                'clock_in_location' => $attendance->clock_in_location,
                'clock_out_location' => $attendance->clock_out_location,
            ];
        }

        return $periods;
    }

    /**
     * Calculate total work hours for an attendance record.
     */
    private function calculateAttendanceWorkHours(Attendance $attendance, ?Carbon $clockInCarbon, ?Carbon $clockOutCarbon): float
    {
        if (isset($attendance->total_work_hours) && $attendance->total_work_hours > 0) {
            return (float) $attendance->total_work_hours;
        }
        if (!$clockInCarbon) {
            return 0.0;
        }
        $endRef = $clockOutCarbon ?? $this->now();
        return round(max(0, $clockInCarbon->diffInMinutes($endRef, true)) / 60, 2);
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

    // =============================================================================
    // Utilities
    // =============================================================================

    /**
     * Get the resolved timezone for the current request
     *
     * @return string
     */
    private function getTimezone(): string
    {
        return getTimeZoneBranchByRequest() ?? config('app.timezone');
    }

    /**
     * Current time in the request/app timezone (same as getTimezone()).
     *
     * @return Carbon
     */
    private function now(): Carbon
    {
        return Carbon::now($this->getTimezone());
    }

    /**
     * Parse a datetime value with proper timezone handling
     *
     * @param mixed $value The datetime value to parse
     * @param string|null $timezone Optional timezone override
     * @return Carbon
     */
    private function parseDateTime($value, ?string $timezone = null): Carbon
    {
        $tz = $timezone ?? $this->getTimezone();
        
        if ($value instanceof Carbon) {
            return $value->copy()->setTimezone($tz);
        }
        
        return Carbon::parse($value, $tz);
    }
}


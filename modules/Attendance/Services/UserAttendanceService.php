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
    /**
     * When set, {@see getTimezone()} returns this instead of calling the global helper (avoids duplicate user queries in one request).
     */
    private ?string $requestTimezoneOverride = null;
    
    /**
     * Cache for user data within a single request to avoid duplicate queries
     */
    private array $userCache = [];

    public function __construct(
        private AttendanceConstraintService $constraintService,
        private AttendanceService $attendanceService
    ) {}

    // =============================================================================
    // Public API
    // =============================================================================

    /**
     * Get user with required relationships, using cache to avoid duplicate queries
     */
    private function getUserWithRelationships(UuidInterface|string $userId): User
    {
        $userIdString = is_string($userId) ? $userId : $userId->toString();
        
        if (!isset($this->userCache[$userIdString])) {
            $this->userCache[$userIdString] = User::query()
                ->with([
                    'professionalData.attendanceConstraint',
                    'userProfessionalData.branch.address.country.timezones',
                    'userProfessionalData.department',
                ])
                ->findOrFail($userIdString);
        }
        
        return $this->userCache[$userIdString];
    }

    /**
     * Reuse the resolved auth user and only load relations that are missing (avoids a second SELECT on users).
     */
    private function ensureUserWithConstraintRelations(User $user): User
    {
        $userIdString = (string) $user->getKey();

        if (isset($this->userCache[$userIdString])) {
            return $this->userCache[$userIdString];
        }

        $user->loadMissing([
            'professionalData.attendanceConstraint',
            'userProfessionalData.branch.address.country.timezones',
            'userProfessionalData.department',
        ]);

        return $this->userCache[$userIdString] = $user;
    }

    /**
     * Get work rules/constraints for a user
     *
     * Pass the authenticated {@see User} when available to avoid a duplicate users-table query (e.g. mobile "today" constraint).
     *
     * @param User|UuidInterface|string $userOrId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
     */
    public function getUserConstraints(User|UuidInterface|string $userOrId, ?string $date = null): array
    {
        $user = $userOrId instanceof User
            ? $this->ensureUserWithConstraintRelations($userOrId)
            : $this->getUserWithRelationships($userOrId);

        $previousTz = $this->requestTimezoneOverride;
        $this->requestTimezoneOverride = $this->timezoneFromUserBranch($user);

        try {
            $timezone = $this->getTimezone();
            $targetDate = $date ?? $this->now()->format('Y-m-d');
            $dateCarbon = $this->parseDateTime($targetDate, $timezone);

            $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $targetDate, $timezone);
            [$attendances, $currentAttendance] = $this->fetchDayAttendancesAndCurrentOpen($user, $dateCarbon);

            if (isset($workRules['all_work_periods']) && is_array($workRules['all_work_periods'])) {
                $earlyClockInRules = $workRules['early_clock_in_rules'] ?? null;
                $workRules['all_work_periods'] = $this->enhancePeriodsWithAttendance(
                    $workRules['all_work_periods'],
                    $attendances,
                    $dateCarbon,
                    is_array($earlyClockInRules) ? $earlyClockInRules : [],
                    $currentAttendance
                );
            }

            return [
                'user_id' => (string) $user->id,
                'user_name' => $user->name,
                'date' => $targetDate,
                'work_rules' => $this->filterWorkRules($workRules),
            ];
        } finally {
            $this->requestTimezoneOverride = $previousTz;
        }
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
     * One query: attendances for the target day (by start_time / clock_in_time) plus any still-open shift
     * (clock_in set, clock_out null) so overnight sessions are included without a second DB round-trip.
     *
     * @return array{0: Collection<int, Attendance>, 1: Attendance|null}
     */
    private function fetchDayAttendancesAndCurrentOpen(User $user, Carbon $date): array
    {
        $timezone = $this->getTimezone();
        $dateInTz = $date->copy()->setTimezone($timezone);

        $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
        $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');

        $columns = [
            'id',
            'user_id',
            'status',
            'timezone',
            'start_time',
            'clock_in_time',
            'clock_out_time',
            'late_minutes',
            'overtime_hours',
            'total_work_hours',
            'clock_in_location',
            'clock_out_location',
        ];

        // Keep this query range-based and narrow to avoid large filesort memory pressure.
        $dayRecords = Attendance::query()
            ->select($columns)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($dayStartUtc, $dayEndUtc) {
                $query->whereBetween('start_time', [$dayStartUtc, $dayEndUtc])
                    ->orWhere(function ($inner) use ($dayStartUtc, $dayEndUtc) {
                        $inner->whereNull('start_time')
                            ->whereBetween('clock_in_time', [$dayStartUtc, $dayEndUtc]);
                    });
            })
            ->orderByRaw('COALESCE(start_time, clock_in_time) ASC')
            ->get();

        // Fetch latest open attendance separately to avoid combining it in a broad OR query.
        $currentOpen = Attendance::query()
            ->select($columns)
            ->where('user_id', $user->id)
            ->whereNotNull('clock_in_time')
            ->whereNull('clock_out_time')
            ->orderByDesc('clock_in_time')
            ->first();

        if ($currentOpen !== null && !$dayRecords->contains('id', $currentOpen->id)) {
            $dayRecords->push($currentOpen);
        }

        $records = $dayRecords
            ->sortBy(static fn (Attendance $attendance) => $attendance->start_time ?? $attendance->clock_in_time)
            ->values();

        return [$records, $currentOpen];
    }

    /**
     * Enhance periods with attendance records
     *
     * @param array $periods
     * @param Collection $attendances
     * @param Carbon $date
     * @return array
     */
    private function enhancePeriodsWithAttendance(
        array $periods,
        Collection $attendances,
        Carbon $date,
        array $earlyClockInRules,
        ?Attendance $currentAttendance = null
    ): array {
        $timezone = $this->getTimezone();
        $now = Carbon::now($timezone);

        $periodBounds = [];
        foreach ($periods as $idx => $period) {
            $periodBounds[$idx] = [
                'start' => $this->parsePeriodTime($period, 'start', $date),
                'end' => $this->parsePeriodTime($period, 'end', $date),
            ];
        }

        $activePeriodIndex = $this->resolveSingleActivePeriodIndex(
            $periodBounds,
            $now,
            $earlyClockInRules,
            $currentAttendance
        );

        $out = [];
        foreach ($periods as $idx => $period) {
            $periodStart = $periodBounds[$idx]['start'];
            $periodEnd = $periodBounds[$idx]['end'];

            $totalWorkHours = $this->calculatePeriodWorkHours($periodStart, $periodEnd);
            $periodAttendances = $this->findAttendancesInPeriod($attendances, $periodStart, $periodEnd);

            // Clock-in / early window (unchanged) — drives can_clock_in per period
            $isActiveByTime = $this->isPeriodActiveIncludingEarly($periodStart, $periodEnd, $now, $earlyClockInRules);
            $isActiveForDisplay = $activePeriodIndex !== null && $idx === $activePeriodIndex;

            $out[] = $this->mergePeriodData(
                $period,
                $totalWorkHours,
                $periodAttendances,
                $isActiveByTime,
                $isActiveForDisplay,
                $earlyClockInRules,
                $currentAttendance
            );
        }

        return $out;
    }

    /**
     * Pick exactly one "current" period for {@see mergePeriodData} `is_active`:
     * open shift (clock in, no clock out) → period whose bounds contain that clock-in; else first period where now falls (incl. early window).
     *
     * @param array<int, array{start: Carbon, end: Carbon}> $periodBounds
     */
    private function resolveSingleActivePeriodIndex(
        array $periodBounds,
        Carbon $now,
        array $earlyClockInRules,
        ?Attendance $currentAttendance
    ): ?int {
        if ($currentAttendance !== null) {
            foreach ($periodBounds as $idx => $bounds) {
                if ($this->isAttendanceClockInWithinPeriod($currentAttendance, $bounds['start'], $bounds['end'])) {
                    return $idx;
                }
            }
        }

        foreach ($periodBounds as $idx => $bounds) {
            if ($this->isPeriodActiveIncludingEarly($bounds['start'], $bounds['end'], $now, $earlyClockInRules)) {
                return $idx;
            }
        }

        return null;
    }

    private function isAttendanceClockInWithinPeriod(
        Attendance $attendance,
        Carbon $periodStart,
        Carbon $periodEnd
    ): bool {
        if (!$attendance->clock_in_time) {
            return false;
        }

        $attendanceTz = $attendance->timezone ?? $periodStart->getTimezone();
        $clockInCarbon = $attendance->clock_in_time instanceof Carbon
            ? $attendance->clock_in_time->copy()->setTimezone($attendanceTz)
            : Carbon::parse($attendance->clock_in_time, $attendanceTz);

        $clockInInPeriodTz = $clockInCarbon->copy()->setTimezone($periodStart->getTimezone());

        return $clockInInPeriodTz->between($periodStart, $periodEnd, true);
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

        $timezone = $this->getTimezone();

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
            ->filter(fn (Attendance $attendance) => $this->isAttendanceClockInWithinPeriod($attendance, $periodStart, $periodEnd))
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
     * @param bool $isActiveByTime Now inside this period or its early clock-in window (drives can_clock_in)
     * @param bool $isActiveForDisplay Single "current" period for UI (open shift period, else time-based)
     * @return array
     */
    private function mergePeriodData(
        array $period,
        float $totalWorkHours,
        array $attendance,
        bool $isActiveByTime,
        bool $isActiveForDisplay,
        array $earlyClockInRules,
        ?Attendance $currentAttendance = null
    ): array {
        $cleanedPeriod = $period;
        unset($cleanedPeriod['period_start_time_carbon'], $cleanedPeriod['period_end_time_carbon']);
        
        $totalHoursPresent = 0;
        foreach ($attendance as $att) {
            $totalHoursPresent += $att['total_hours_present'] ?? 0;
        }
        
        $hasActiveAttendance = collect($attendance)->contains(function ($att) {
            return $att['status'] === 'active';
        });

        $canClockIn = $isActiveByTime && ! $hasActiveAttendance && $currentAttendance === null;

        return array_merge($cleanedPeriod, [
            'total_work_hours' => $totalWorkHours,
            'is_active' => $isActiveForDisplay,
            'total_hours_present' => round($totalHoursPresent, 2),
            'can_clock_in' => $canClockIn,
            'can_clock_out' => $currentAttendance !== null && $isActiveForDisplay,
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
            return $this->attendanceService->getCurrentAttendance($userIdUuid, false);
        } catch (\Exception $e) {
            return null;
        }
    }

    // =============================================================================
    // Attendance History
    // =============================================================================

    public function getUserAttendanceHistoryMobileApi(
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

        // Build date range in user's timezone, then convert to UTC for database query
        $rangeStart = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        $monthEnd = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->endOfMonth();
        $rangeEnd = $monthEnd->gt($now) ? $now->copy()->endOfDay() : $monthEnd;

        // Convert to UTC for database query (database stores times in UTC)
        $rangeStartUtc = $rangeStart->copy()->setTimezone('UTC');
        $rangeEndUtc = $rangeEnd->copy()->setTimezone('UTC');

        $historyColumns = [
            'id',
            'user_id',
            'company_id',
            'status',
            'timezone',
            'start_time',
            'end_time',
            'clock_in_time',
            'clock_out_time',
            'late_minutes',
            'overtime_hours',
            'total_work_hours',
            'clock_in_location',
            'clock_out_location',
        ];

        $allAttendances = Attendance::query()
            ->select($historyColumns)
            ->where('user_id', $user->id)
            ->where('status', '!=', Attendance::STATUS_WAITING)
            ->where(function ($q) use ($rangeStartUtc, $rangeEndUtc) {
                $q->whereBetween('start_time', [$rangeStartUtc, $rangeEndUtc])
                    ->orWhere(function ($q2) use ($rangeStartUtc, $rangeEndUtc) {
                        $q2->whereNull('start_time')
                            ->whereBetween('clock_in_time', [$rangeStartUtc, $rangeEndUtc]);
                    });
            })
            ->get()
            ->sortByDesc(function (Attendance $a) {
                $ref = $a->start_time ?? $a->clock_in_time;
                if ($ref === null) {
                    return 0;
                }

                return $ref instanceof Carbon ? $ref->timestamp : Carbon::parse($ref)->timestamp;
            })
            ->values();

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

        // Build date range in user's timezone, then convert to UTC for database query
        $rangeStart = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->startOfMonth();
        $monthEnd = Carbon::create($currentYear, $currentMonth, 1, 0, 0, 0, $timezone)->endOfMonth();
        $rangeEnd = $monthEnd->gt($now) ? $now->copy()->endOfDay() : $monthEnd;

        // Convert to UTC for database query (database stores times in UTC)
        $rangeStartUtc = $rangeStart->copy()->setTimezone('UTC');
        $rangeEndUtc = $rangeEnd->copy()->setTimezone('UTC');

        $historyColumns = [
            'id',
            'user_id',
            'company_id',
            'status',
            'timezone',
            'start_time',
            'end_time',
            'clock_in_time',
            'clock_out_time',
            'late_minutes',
            'overtime_hours',
            'total_work_hours',
            'clock_in_location',
            'clock_out_location',
        ];

        $allAttendances = Attendance::query()
            ->select($historyColumns)
            ->where('user_id', $user->id)
            ->where('status', '!=', Attendance::STATUS_WAITING)
            ->where(function ($q) use ($rangeStartUtc, $rangeEndUtc) {
                $q->whereBetween('start_time', [$rangeStartUtc, $rangeEndUtc])
                    ->orWhere(function ($q2) use ($rangeStartUtc, $rangeEndUtc) {
                        $q2->whereNull('start_time')
                            ->whereBetween('clock_in_time', [$rangeStartUtc, $rangeEndUtc]);
                    });
            })
            ->get()
            ->sortByDesc(function (Attendance $a) {
                $ref = $a->start_time ?? $a->clock_in_time;
                if ($ref === null) {
                    return 0;
                }

                return $ref instanceof Carbon ? $ref->timestamp : Carbon::parse($ref)->timestamp;
            })
            ->values();

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
     * Rows that share the same scheduled {@see Attendance::$start_time} and {@see Attendance::$end_time}
     * are one logical shift; rows without both bounds stay unmerged (one group per row).
     */
    private function shiftScheduleGroupKey(Attendance $attendance): string
    {
        if ($attendance->start_time !== null && $attendance->end_time !== null) {
            return $this->toCarbon($attendance->start_time)->format('c')
                . '|'
                . $this->toCarbon($attendance->end_time)->format('c');
        }

        return 'id:' . $attendance->id;
    }

    /**
     * Build periods data directly from attendances without heavy constraint lookups.
     * Groups by scheduled start/end; each group exposes first clock-in, last clock-out, and summed metrics.
     */
    private function buildPeriodsFromAttendances(Collection $attendances): array
    {
        if ($attendances->isEmpty()) {
            return [];
        }

        $groups = $attendances->groupBy(fn (Attendance $a) => $this->shiftScheduleGroupKey($a));

        return $groups
            ->sortBy(fn (Collection $group) => $this->earliestClockInTimestampInGroup($group))
            ->map(fn (Collection $group) => $this->buildAggregatedPeriodFromShiftGroup($group))
            ->values()
            ->all();
    }

    /**
     * @param Collection<int, Attendance> $group
     */
    private function earliestClockInTimestampInGroup(Collection $group): int
    {
        $min = null;
        foreach ($group as $attendance) {
            if (!$attendance->clock_in_time) {
                continue;
            }
            $ts = $this->toCarbon($attendance->clock_in_time)->timestamp;
            $min = $min === null ? $ts : min($min, $ts);
        }

        return $min ?? PHP_INT_MAX;
    }

    /**
     * @param Collection<int, Attendance> $group
     */
    private function buildAggregatedPeriodFromShiftGroup(Collection $group): array
    {
        $sortedByClockIn = $group->sortBy(function (Attendance $a) {
            if (!$a->clock_in_time) {
                return PHP_INT_MAX;
            }

            return $this->toCarbon($a->clock_in_time)->timestamp;
        })->values();

        $firstClockInCarbon = null;
        $lastClockOutCarbon = null;
        $firstClockInAttendance = null;
        $lastClockOutAttendance = null;
        $totalWorkHours = 0.0;
        $totalLateMinutes = 0;
        $totalOvertimeHours = 0.0;

        foreach ($sortedByClockIn as $attendance) {
            $clock = $this->extractAttendanceClockData($attendance);
            $clockInCarbon = $clock['clock_in_carbon'];
            $clockOutCarbon = $clock['clock_out_carbon'];

            $totalWorkHours += $this->calculateAttendanceWorkHours($attendance, $clockInCarbon, $clockOutCarbon);
            $totalLateMinutes += (int) ($attendance->late_minutes ?? 0);
            $totalOvertimeHours += (float) ($attendance->overtime_hours ?? 0);

            if ($clockInCarbon !== null) {
                if ($firstClockInCarbon === null || $clockInCarbon->lt($firstClockInCarbon)) {
                    $firstClockInCarbon = $clockInCarbon;
                    $firstClockInAttendance = $attendance;
                }
            }

            if ($clockOutCarbon !== null) {
                if ($lastClockOutCarbon === null || $clockOutCarbon->gt($lastClockOutCarbon)) {
                    $lastClockOutCarbon = $clockOutCarbon;
                    $lastClockOutAttendance = $attendance;
                }
            }
        }

        $representative = $sortedByClockIn->first();
        $scheduledStart = $representative->start_time
            ? $this->toCarbon($representative->start_time)->format('H:i')
            : null;
        $scheduledEnd = $representative->end_time
            ? $this->toCarbon($representative->end_time)->format('H:i')
            : null;

        $firstIn = $firstClockInCarbon?->format('H:i');
        $lastOut = $lastClockOutCarbon?->format('H:i');

        return [
            'start_time' => $scheduledStart,
            'end_time' => $scheduledEnd,
            'clock_in_time' => $firstIn,
            'clock_out_time' => $lastOut,
            'work_hours' => $this->formatHoursToTime($totalWorkHours),
            'delay_hours' => $this->formatMinutesToTime($totalLateMinutes),
            'overtime_hours' => $this->formatHoursToTime($totalOvertimeHours),
            'clock_in_location' => $firstClockInAttendance?->clock_in_location,
            'clock_out_location' => $lastClockOutAttendance?->clock_out_location,
        ];
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
        if ($this->requestTimezoneOverride !== null) {
            return $this->requestTimezoneOverride;
        }

        return getTimeZoneBranchByRequest() ?? config('app.timezone');
    }

    /**
     * Prefer timezone from the user's branch (already eager-loaded) to avoid a second User query via getTimeZoneBranchByRequest().
     */
    private function timezoneFromUserBranch(User $user): string
    {
        $timezones = $user->userProfessionalData?->branch?->address?->country?->timezones;
        if (is_array($timezones) && isset($timezones[0]['zoneName']) && is_string($timezones[0]['zoneName'])) {
            return $timezones[0]['zoneName'];
        }

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

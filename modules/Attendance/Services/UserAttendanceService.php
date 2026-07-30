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
     * Get work rules/constraints for a user
     *
     * Pass the authenticated {@see User} when available to avoid a duplicate users-table query (e.g. mobile "today" constraint).
     *
     * @param User|UuidInterface|string $userOrId
     * @param string|null $date Optional date (Y-m-d format), defaults to today
     * @return array
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
            'userProfessionalData.attendanceConstraint',
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
                    $currentAttendance,
                    $workRules
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
        ?Attendance $currentAttendance = null,
        array $workRules = []
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

            // Net minutes already credited in this scheduled period (in-memory — rows were
            // already loaded), so window boundaries account for completed attendances.
            $alreadyWorked = (int) round($attendances
                ->filter(fn ($a) => !empty($a->clock_in_time)
                    && $a->start_time === $periodStart->format('Y-m-d H:i:s')
                    && $a->end_time === $periodEnd->format('Y-m-d H:i:s'))
                ->sum(fn ($a) => (float) $a->total_work_hours) * 60);

            $window = $this->computePeriodWindow($periodStart, $periodEnd, $now, $workRules, $timezone, $alreadyWorked);
            $hasAnyClockIn = collect($periodAttendances)->contains(fn ($att) => !empty($att['clock_in_time']));
            $hasActiveAttendance = collect($periodAttendances)->contains(fn ($att) => ($att['status'] ?? null) === 'active');
            $periodIsAbsent = collect($periodAttendances)->contains(fn ($att) => ($att['status'] ?? null) === 'absent')
                || ($window->absentAt->isPast() && !$hasAnyClockIn);

            $isFirstClockIn = !$hasAnyClockIn && !$hasActiveAttendance;
            $latestAllowed = $isFirstClockIn
                ? ($window->firstClockInDeadline ?? $window->lastClockInAt)
                : $window->lastClockInAt;

            $isActiveByTime = $now->between($window->earliestClockIn, $latestAllowed, true);
            $isActiveForDisplay = $activePeriodIndex !== null && $idx === $activePeriodIndex;

            $out[] = $this->mergePeriodData(
                $period,
                $totalWorkHours,
                $periodAttendances,
                $isActiveByTime,
                $isActiveForDisplay,
                $earlyClockInRules,
                $currentAttendance,
                $window,
                $periodIsAbsent,
                $workRules
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
    private function parsePeriodTime(array $period, string $type, Carbon $date, ?string $timezone = null): Carbon
    {
        $timeKey = "{$type}_time";
        $timezone = $timezone ?? $this->getTimezone();

        // Always parse time fresh with the correct timezone to ensure accurate comparisons
        // Pre-set Carbon instances from constraint service may have timezone context mismatches
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
        ?Attendance $currentAttendance = null,
        ?\Modules\Attendance\Domain\Time\ShiftWindow $window = null,
        bool $periodIsAbsent = false,
        array $workRules = []
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

        $canClockIn = $isActiveByTime && ! $hasActiveAttendance && $currentAttendance === null && ! $periodIsAbsent;

        $extra = [];
        if ($window !== null) {
            $extra = [
                'work_window_start' => $window->workWindowStart->toIso8601String(),
                'work_window_end' => $window->workWindowEnd->toIso8601String(),
                'can_clock_in_from' => $window->earliestClockIn->toIso8601String(),
                'can_clock_in_until' => $window->firstClockInDeadline?->toIso8601String() ?? $window->lastClockInAt->toIso8601String(),
                'can_clock_out_until' => $window->lastClockOutAt->toIso8601String(),
                'absent_at' => $window->absentAt->toIso8601String(),
                'required_work_minutes' => $window->requiredWorkMinutes,
                'is_absent' => $periodIsAbsent,
            ];
        }

        return array_merge($cleanedPeriod, [
            'total_work_hours' => $totalWorkHours,
            'is_active' => $isActiveForDisplay && $canClockIn,
            'total_hours_present' => round($totalHoursPresent, 2),
            'can_clock_in' => $canClockIn,
            'can_clock_out' => $currentAttendance !== null && $isActiveForDisplay,
            'early_clock_in_rules' => $this->buildEarlyClockInRulesForResponse($earlyClockInRules),
            'extension_hours_shift' => isset($workRules['extension_minutes']) ? round(((int) $workRules['extension_minutes']) / 60, 2) : 0,
            'can_clock_in_before' => $workRules['can_clock_in_before_minutes'] ?? null,
            'attendance' => $attendance,
        ], $extra);
    }

    /**
     * Compute the V2 shift window for one period from the day-level rules emitted by
     * AttendanceConstraintService (early/extension/deadline/overtime flags).
     */
    private function computePeriodWindow(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $now,
        array $workRules,
        string $timezone,
        int $alreadyWorkedMinutesInPeriod = 0
    ): \Modules\Attendance\Domain\Time\ShiftWindow {
        return (new \Modules\Attendance\Domain\Time\ShiftWindowCalculator())->compute(
            new \Modules\Attendance\Domain\Time\ShiftWindowInput(
                scheduledStart: \Carbon\CarbonImmutable::parse($periodStart->format('Y-m-d H:i:s'), $timezone),
                scheduledEnd: \Carbon\CarbonImmutable::parse($periodEnd->format('Y-m-d H:i:s'), $timezone),
                clockIn: \Carbon\CarbonImmutable::parse($now->format('Y-m-d H:i:s'), $timezone),
                earlyWindowMinutes: (int) ($workRules['early_clock_in_minutes'] ?? 0),
                extensionMinutes: (int) ($workRules['extension_minutes'] ?? 0),
                canClockInBeforeMinutes: isset($workRules['can_clock_in_before_minutes'])
                    ? (int) $workRules['can_clock_in_before_minutes']
                    : null,
                maxOverTimeHours: (float) ($workRules['max_over_time'] ?? 0.0),
                alreadyWorkedMinutesInPeriod: $alreadyWorkedMinutesInPeriod,
                overtimeFlags: \Modules\Attendance\Domain\Calculator\OvertimeFlags::fromArray($workRules['overtime_rules'] ?? null),
                timezone: $timezone,
            )
        );
    }

    /**
     * Build early clock-in rules for API response.
     */
    private function buildEarlyClockInRulesForResponse(array $earlyClockInRules): array
    {
        return [
            'prevent_early_clock_in' => (bool) ($earlyClockInRules['prevent_early_clock_in'] ?? false),
            'early_period' => \Modules\Attendance\Support\EarlyClockInRules::minutes($earlyClockInRules),
            'early_unit' => 'minutes',
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
            'additional_locations' => $workRules['additional_locations'] ?? [],
            '_debug' => $workRules['_debug'] ?? null,
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

        $earlyMinutes = \Modules\Attendance\Support\EarlyClockInRules::minutes($earlyClockInRules);
        if ($earlyMinutes <= 0) {
            return false;
        }

        $earliestAllowed = $periodStart->copy()->subMinutes($earlyMinutes);

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

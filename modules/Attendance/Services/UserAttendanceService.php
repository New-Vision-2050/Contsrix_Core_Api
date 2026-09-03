<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Support\ManualAttendanceStatus;
use Modules\Attendance\Support\PublicHolidayDates;
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
                    'manualAttendanceOverrides',
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
            'manualAttendanceOverrides',
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

            $override = ManualAttendanceStatus::activeOn($user, $targetDate);

            // Resolved before the rules are built, not after: a required-attendance override
            // needs the day's real periods, and an official holiday empties them. Flipping
            // day_status back afterwards cannot rebuild them, which would leave the endpoint
            // reporting a work day that carries no can_clock_in_until (INV-21).
            $publicHolidays = $override === ManualAttendanceStatus::REQUIRED_ATTENDANCE
                ? PublicHolidayDates::none()
                : null;

            $workRules = $this->constraintService->getTodaysWorkRulesForUser($user, $targetDate, $timezone, $publicHolidays);
            $workRules = $this->applyManualAttendanceOverride($override, $workRules);
            if (\Modules\Attendance\Support\AttendanceType::userIsFlexible($user)) {
                $workRules = \Modules\Attendance\Support\FlexibleWorkDay::applyToWorkRules(
                    $workRules,
                    $targetDate,
                    $timezone
                );
            } else {
                $workRules['attendance_type'] = \Modules\Attendance\Support\AttendanceType::REGULAR;
            }
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

            $workRules['out_zone_warning'] = config('attendance.out_zone_confirm_enabled', true)
                ? \Modules\Attendance\Support\OutZoneClockOutWarning::payload($currentAttendance)
                : null;

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
     * Applies a persistent manual attendance status override (set via the sub-entity
     * "attendance-status" endpoint) on top of the computed work rules. Active from
     * `user_manual_attendance_overrides` (INV-18). When until on a given range
     * is null that range stays open-ended until punched by required_attendance.
     * After until expires, holiday automatically falls back to required attendance.
     *
     * Takes the already-resolved status rather than the user, because the caller must know
     * it before building the rules: `required_attendance` has to suppress the public-holiday
     * override, which this method could not undo.
     */
    private function applyManualAttendanceOverride(?string $status, array $workRules): array
    {
        if ($status === null) {
            return $workRules;
        }

        if ($status === ManualAttendanceStatus::HOLIDAY) {
            $workRules['day_status'] = 'holiday';
            $workRules['is_holiday'] = true;
            $workRules['reason'] = 'Manual holiday override.';
            // A constraint holiday returns no periods, so a manual one must not either —
            // otherwise the periods still carry can_clock_in and the app offers a
            // clock-in button on a day it has just been told is a holiday.
            $workRules['all_work_periods'] = [];
            $workRules['current_work_period'] = null;
        } else {
            $workRules['day_status'] = 'work_day';
            $workRules['is_holiday'] = false;
            $workRules['reason'] = 'Manual required-attendance override.';
        }

        return $workRules;
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
     * One query: attendances for the target day plus any still-open shift
     * (clock_in set, clock_out null) so overnight sessions are included without a second DB round-trip.
     *
     * Day membership is the same key the calendar uses: `business_date`, then
     * `start_time` / `clock_in_time` for rows that never received a business date
     * (flexible stores start_time = clock-in, so a start_time-only filter misses them).
     *
     * @return array{0: Collection<int, Attendance>, 1: Attendance|null}
     */
    private function fetchDayAttendancesAndCurrentOpen(User $user, Carbon $date): array
    {
        $timezone = $this->getTimezone();
        $dateInTz = $date->copy()->setTimezone($timezone);
        $businessDate = $dateInTz->toDateString();

        $dayStartUtc = $dateInTz->copy()->startOfDay()->setTimezone('UTC');
        $dayEndUtc = $dateInTz->copy()->endOfDay()->setTimezone('UTC');

        $columns = [
            'id',
            'user_id',
            'status',
            'timezone',
            'start_time',
            'end_time',
            'business_date',
            'clock_in_time',
            'clock_out_time',
            'expected_clock_out_time',
            'shift_end_method',
            'out_zone_warning_at',
            'notes',
            'late_minutes',
            'overtime_hours',
            'total_work_hours',
            'clock_in_location',
            'clock_out_location',
            'location_tracking',
        ];

        // Keep this query range-based and narrow to avoid large filesort memory pressure.
        $dayRecords = Attendance::query()
            ->select($columns)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($dayStartUtc, $dayEndUtc, $businessDate) {
                $query->whereDate('business_date', $businessDate)
                    ->orWhereBetween('start_time', [$dayStartUtc, $dayEndUtc])
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
        [$earlyMinutes, $extensionMinutes] = $this->earlyAndExtensionMinutes($workRules);

        $periodBounds = [];
        foreach ($periods as $idx => $period) {
            $start = $this->parsePeriodTime($period, 'start', $date);
            $end = $this->parsePeriodTime($period, 'end', $date);
            $periodBounds[$idx] = [
                'start' => $start,
                'end' => $end,
                // Allowed punch window, not the scheduled block. Early clock-in at 08:00
                // for an 08:30 start is valid and must still attach to this period (INV-16).
                'matchStart' => $start->copy()->subMinutes($earlyMinutes),
                'matchEnd' => $end->copy()->addMinutes($extensionMinutes),
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

            $isFlexiblePeriod = \Modules\Attendance\Support\AttendanceType::isFlexible(
                $period['attendance_type'] ?? $workRules['attendance_type'] ?? null
            );
            $totalWorkHours = $isFlexiblePeriod
                ? round(\Modules\Attendance\Support\FlexibleWorkDay::requiredMinutesFromWorkRules($workRules) / 60, 2)
                : $this->calculatePeriodWorkHours($periodStart, $periodEnd);
            $periodAttendances = $this->findAttendancesInPeriod(
                $attendances,
                $periodStart,
                $periodEnd,
                $periodBounds[$idx]['matchStart'],
                $periodBounds[$idx]['matchEnd']
            );

            // Net minutes already credited in this scheduled period (in-memory — rows were
            // already loaded), so window boundaries account for completed attendances.
            $alreadyWorked = $isFlexiblePeriod
                ? (int) round($attendances
                    ->filter(fn ($a) => ! empty($a->clock_in_time))
                    ->sum(fn ($a) => (float) $a->total_work_hours) * 60)
                : (int) round($attendances
                    ->filter(fn ($a) => ! empty($a->clock_in_time)
                        && $a->start_time === $periodStart->format('Y-m-d H:i:s')
                        && $a->end_time === $periodEnd->format('Y-m-d H:i:s'))
                    ->sum(fn ($a) => (float) $a->total_work_hours) * 60);

            $window = $this->computePeriodWindow($periodStart, $periodEnd, $now, $workRules, $timezone, $alreadyWorked);
            $hasAnyClockIn = collect($periodAttendances)->contains(fn ($att) => !empty($att['clock_in_time']));
            $hasActiveAttendance = collect($periodAttendances)->contains(fn ($att) => ($att['status'] ?? null) === 'active');
            // A real punch on this period outranks a leftover absent status (INV-16).
            $periodIsAbsent = ! $hasAnyClockIn && $window->absentAt->isPast();

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
     * @param array<int, array{start: Carbon, end: Carbon, matchStart?: Carbon, matchEnd?: Carbon}> $periodBounds
     */
    private function resolveSingleActivePeriodIndex(
        array $periodBounds,
        Carbon $now,
        array $earlyClockInRules,
        ?Attendance $currentAttendance
    ): ?int {
        if ($currentAttendance !== null) {
            foreach ($periodBounds as $idx => $bounds) {
                if ($this->attendanceBelongsToPeriod(
                    $currentAttendance,
                    $bounds['start'],
                    $bounds['end'],
                    $bounds['matchStart'] ?? $bounds['start'],
                    $bounds['matchEnd'] ?? $bounds['end']
                )) {
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
     * Regular clock-in stores start_time = scheduled period start, even when the
     * punch is in the early window. Match that row to the period, or match any
     * punch whose clock-in falls in the allowed window (early + extension).
     */
    private function attendanceBelongsToPeriod(
        Attendance $attendance,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?Carbon $matchStart = null,
        ?Carbon $matchEnd = null
    ): bool {
        if (empty($attendance->clock_in_time)) {
            return false;
        }

        if ($this->attendanceScheduledStartMatches($attendance, $periodStart)) {
            return true;
        }

        return $this->isAttendanceClockInWithinPeriod(
            $attendance,
            $matchStart ?? $periodStart,
            $matchEnd ?? $periodEnd
        );
    }

    private function attendanceScheduledStartMatches(Attendance $attendance, Carbon $periodStart): bool
    {
        if (empty($attendance->start_time)) {
            return false;
        }

        $attendanceTz = $attendance->timezone ?? $periodStart->getTimezone();
        $storedStart = $attendance->start_time instanceof Carbon
            ? $attendance->start_time->copy()->setTimezone($attendanceTz)
            : Carbon::parse((string) $attendance->start_time, $attendanceTz);

        return $storedStart->copy()->setTimezone($periodStart->getTimezone())->equalTo($periodStart);
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
     * Find punches that belong to a scheduled period.
     *
     * Matches a real clock-in in the allowed window (early + extension), or a
     * regular row whose stored start_time is this period's scheduled start.
     * Leftover absent/waiting rows with no punch stay out of the payload.
     *
     * @param Collection $attendances
     * @return array
     */
    private function findAttendancesInPeriod(
        Collection $attendances,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?Carbon $matchStart = null,
        ?Carbon $matchEnd = null
    ): array {
        return $attendances
            ->filter(fn (Attendance $attendance) => $this->attendanceBelongsToPeriod(
                $attendance,
                $periodStart,
                $periodEnd,
                $matchStart,
                $matchEnd
            ))
            ->map(fn ($attendance) => $this->formatAttendanceForPeriod($attendance, $periodStart, $periodEnd))
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
            'id' => $attendance->id !== null ? (string) $attendance->id : null,
            'status' => $attendance->status ?? 'scheduled',
            'date' => $clockInCarbon?->format('Y-m-d') ?? $periodStart->format('Y-m-d'),
            'start_time' => $periodStart->format('H:i'),
            'end_time' => $periodEnd->format('H:i'),
            'clock_in_time' => $clock['clock_in_time'],
            'clock_out_time' => $clock['clock_out_time'],
            'total_hours_present' => $totalHoursPresent,
            'clock_out_cause' => $this->resolveClockOutCause($attendance),
            'shift_end_method' => $attendance->shift_end_method ?: null,
            'expected_clock_out_time' => $this->formatStoredWallClock($attendance->expected_clock_out_time),
            'clock_out_location' => $this->resolveClockOutLocation($attendance),
            'notes' => $attendance->notes ?: null,
        ];
    }

    /**
     * Prefer the location persisted at close. Older auto_out_zone rows never wrote
     * clock_out_location, so fall back to the last tracking ping at or before clock-out.
     * Skip GPS-default / Null Island samples (near 0,0) and points impossibly far
     * from clock-in — those are bad device pings, not a real clock-out location.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function resolveClockOutLocation(Attendance $attendance): ?array
    {
        $anchor = $this->normalizeLatLng($attendance->clock_in_location);
        $stored = $this->normalizeLatLng($attendance->clock_out_location);
        if ($stored !== null && $this->isPlausibleClockOutLocation($stored, $anchor)) {
            return $stored;
        }

        if (empty($attendance->clock_out_time)) {
            return null;
        }

        return $this->lastTrackingPointAtOrBefore(
            is_array($attendance->location_tracking) ? $attendance->location_tracking : [],
            $attendance->clock_out_time,
            $attendance->timezone ?? $this->getTimezone(),
            $anchor
        );
    }

    /**
     * @param  list<array<string, mixed>>  $points
     * @param  array{latitude: float, longitude: float}|null  $anchor
     * @return array{latitude: float, longitude: float}|null
     */
    private function lastTrackingPointAtOrBefore(
        array $points,
        mixed $clockOutTime,
        string $timezone,
        ?array $anchor = null
    ): ?array {
        $deadline = $clockOutTime instanceof Carbon
            ? $clockOutTime->copy()->setTimezone($timezone)
            : Carbon::parse((string) $clockOutTime, $timezone);

        $lastWithTime = null;
        $lastWithoutTime = null;

        foreach ($points as $point) {
            $coords = $this->normalizeLatLng($point);
            if ($coords === null || ! $this->isPlausibleClockOutLocation($coords, $anchor)) {
                continue;
            }

            if (empty($point['timestamp'])) {
                $lastWithoutTime = $coords;
                continue;
            }

            try {
                $at = Carbon::parse((string) $point['timestamp'], $timezone);
            } catch (\Throwable) {
                $lastWithoutTime = $coords;
                continue;
            }

            if ($at->greaterThan($deadline)) {
                continue;
            }

            $lastWithTime = $coords;
        }

        return $lastWithTime ?? $lastWithoutTime;
    }

    /**
     * Reject GPS-default "Null Island" (~0,0) and points more than 500 km from clock-in.
     *
     * @param  array{latitude: float, longitude: float}  $coords
     * @param  array{latitude: float, longitude: float}|null  $anchor
     */
    private function isPlausibleClockOutLocation(array $coords, ?array $anchor): bool
    {
        $lat = $coords['latitude'];
        $lng = $coords['longitude'];

        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return false;
        }

        if (abs($lat) < 1.0 && abs($lng) < 1.0) {
            return false;
        }

        if ($anchor === null) {
            return true;
        }

        $metres = \Modules\Attendance\Support\GeofenceMatch::distanceInMetres(
            $anchor['latitude'],
            $anchor['longitude'],
            $lat,
            $lng
        );

        return $metres <= 500_000;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private function normalizeLatLng(mixed $location): ?array
    {
        if (! is_array($location) || ! isset($location['latitude'], $location['longitude'])) {
            return null;
        }

        if (! is_numeric($location['latitude']) || ! is_numeric($location['longitude'])) {
            return null;
        }

        return [
            'latitude' => (float) $location['latitude'],
            'longitude' => (float) $location['longitude'],
        ];
    }

    /**
     * One field for the app: null while still open, the stored method for auto closes,
     * `manual` when the employee clocked out and shift_end_method was never written.
     * Auto values include auto_max_ot, auto_next_shift, auto_out_zone, auto_no_location.
     */
    private function resolveClockOutCause(Attendance $attendance): ?string
    {
        if (empty($attendance->clock_out_time)) {
            return null;
        }

        $method = is_string($attendance->shift_end_method) ? trim($attendance->shift_end_method) : '';

        return $method !== '' ? $method : 'manual';
    }

    private function formatStoredWallClock(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse((string) $value, $this->getTimezone())->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return (string) $value;
        }
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
            'is_active' => $canClockIn || ($currentAttendance !== null && $isActiveForDisplay),
            'total_hours_present' => round($totalHoursPresent, 2),
            'can_clock_in' => $canClockIn,
            'can_clock_out' => $currentAttendance !== null && $isActiveForDisplay,
            'early_clock_in_rules' => $this->buildEarlyClockInRulesForResponse($earlyClockInRules),
            'extension_hours_shift' => isset($workRules['extension_minutes'])
                ? (int) $workRules['extension_minutes']
                : 0,
            'extension_minutes' => isset($workRules['extension_minutes'])
                ? (int) $workRules['extension_minutes']
                : 0,
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
        [$earlyMinutes, $extensionMinutes] = $this->earlyAndExtensionMinutes($workRules);

        $canClockInBefore = array_key_exists('can_clock_in_before_minutes', $workRules)
            ? (isset($workRules['can_clock_in_before_minutes']) ? (int) $workRules['can_clock_in_before_minutes'] : null)
            : (isset($workRules['clock_in_deadline_rules']['can_clock_in_before_minutes'])
                ? (int) $workRules['clock_in_deadline_rules']['can_clock_in_before_minutes']
                : null);

        $isFlexible = \Modules\Attendance\Support\AttendanceType::isFlexible($workRules['attendance_type'] ?? null);

        return (new \Modules\Attendance\Domain\Time\ShiftWindowCalculator())->compute(
            new \Modules\Attendance\Domain\Time\ShiftWindowInput(
                scheduledStart: \Carbon\CarbonImmutable::parse($periodStart->format('Y-m-d H:i:s'), $timezone),
                scheduledEnd: \Carbon\CarbonImmutable::parse($periodEnd->format('Y-m-d H:i:s'), $timezone),
                clockIn: \Carbon\CarbonImmutable::parse($now->format('Y-m-d H:i:s'), $timezone),
                earlyWindowMinutes: $earlyMinutes,
                extensionMinutes: $extensionMinutes,
                canClockInBeforeMinutes: $canClockInBefore,
                maxOverTimeHours: (float) ($workRules['max_over_time'] ?? 0.0),
                alreadyWorkedMinutesInPeriod: $alreadyWorkedMinutesInPeriod,
                overtimeFlags: \Modules\Attendance\Domain\Calculator\OvertimeFlags::fromArray($workRules['overtime_rules'] ?? null),
                timezone: $timezone,
                requiredWorkMinutesOverride: $isFlexible
                    ? \Modules\Attendance\Support\FlexibleWorkDay::requiredMinutesFromWorkRules($workRules)
                    : null,
                flexibleDay: $isFlexible,
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
            // V2 window rules — required by MockAttendanceService clock-in matching.
            // Stripping these made earliest_clock_in = shift start even when early_period=30.
            'early_clock_in_rules' => $workRules['early_clock_in_rules'] ?? null,
            'early_clock_in_minutes' => $workRules['early_clock_in_minutes'] ?? 0,
            'extension_minutes' => $workRules['extension_minutes'] ?? 0,
            'extension_rules' => $workRules['extension_rules'] ?? null,
            'can_clock_in_before_minutes' => $workRules['can_clock_in_before_minutes'] ?? null,
            'clock_in_deadline_rules' => $workRules['clock_in_deadline_rules'] ?? null,
            'overtime_rules' => $workRules['overtime_rules'] ?? [],
            'max_over_time' => $workRules['max_over_time'] ?? null,
            'attendance_type' => \Modules\Attendance\Support\AttendanceType::normalize(
                $workRules['attendance_type'] ?? null
            ),
            'flexible_required_work_minutes' => $workRules['flexible_required_work_minutes'] ?? null,
            'out_zone_warning' => $workRules['out_zone_warning'] ?? null,
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
    /**
     * @return array{0: int, 1: int} early minutes, extension minutes
     */
    private function earlyAndExtensionMinutes(array $workRules): array
    {
        $earlyMinutes = max(
            (int) ($workRules['early_clock_in_minutes'] ?? 0),
            \Modules\Attendance\Support\EarlyClockInRules::minutes(
                is_array($workRules['early_clock_in_rules'] ?? null) ? $workRules['early_clock_in_rules'] : null
            ),
        );

        $extensionMinutes = (int) ($workRules['extension_minutes'] ?? 0);
        if ($extensionMinutes <= 0) {
            $extRules = is_array($workRules['extension_rules'] ?? null) ? $workRules['extension_rules'] : [];
            if (isset($extRules['extension_minutes'])) {
                $extensionMinutes = (int) $extRules['extension_minutes'];
            } else {
                $extensionMinutes = (int) round(((float) ($extRules['extension_hours'] ?? 0)) * 60);
            }
        }

        return [$earlyMinutes, $extensionMinutes];
    }

    private function getTimezone(): string
    {
        return $this->requestTimezoneOverride
            ?? getTimeZoneBranchByRequest()
            ?? config('app.timezone');
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

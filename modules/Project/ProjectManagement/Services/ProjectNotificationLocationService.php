<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Modules\Attendance\Domain\Time\ShiftWindowCalculator;
use Modules\Attendance\Domain\Time\ShiftWindowInput;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\UserLocation;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Support\EarlyClockInRules;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\User\Models\User;

class ProjectNotificationLocationService
{
    /**
     * Minutes without a GPS update before an employee is reported as `not_connected`.
     * Overridable via config `projectmanagement.notifications.location_stale_minutes`.
     */
    private const LOCATION_STALE_MINUTES = 15;

    /**
     * Statuses hidden from dispatchers unless explicitly requested.
     */
    private const UNAVAILABLE_STATUSES = ['absent', 'out'];

    public function __construct(
        private readonly AttendanceConstraintService $attendanceConstraintService,
        private readonly ShiftWindowCalculator $shiftWindowCalculator,
    ) {}

    public function getProjectEmployeesWithLocations(
        string $projectId,
        float $notificationLat,
        float $notificationLng,
        ?float $radiusMeters = null,
        bool $includeUnavailable = false,
        array $statuses = [],
    ): array {
        $companyId = (string) tenant('id');

        // 1. Get user IDs assigned to the project.
        $userIds = ProjectEmployee::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        // 2. Get the latest user_locations record per user (no date filter).
        //    The track-location API always writes to user_locations, even when
        //    the user has active attendance, so this is the most reliable source.
        //    Note: id is a UUID, so MAX(id) is meaningless; order by recorded_at.
        $latestLocationSubquery = UserLocation::whereIn('user_id', $userIds)
            ->select('user_id', \DB::raw('MAX(recorded_at) as max_recorded_at'))
            ->groupBy('user_id');

        $latestUserLocations = UserLocation::joinSub($latestLocationSubquery, 'latest_locations', function ($join) {
            $join->on('user_locations.user_id', '=', 'latest_locations.user_id')
                ->on('user_locations.recorded_at', '=', 'latest_locations.max_recorded_at');
        })
            ->select('user_locations.*')
            ->orderByDesc('user_locations.created_at')
            ->orderByDesc('user_locations.id')
            ->get()
            ->keyBy('user_id');

        // 3. Batch-query today's attendance rows per user (for status + absence).
        //    Absent rows are kept (R9): is_absent must be visible to derive `absent`.
        //    Holiday rows stay excluded. Rows matched on business_date so absence
        //    records without a clock_in_time still surface; latest clock-in wins
        //    when a user has several rows today (NULL clock-ins sort last).
        $attendances = Attendance::whereIn('user_id', $userIds)
            ->where('is_holiday', false)
            ->where(function ($q) {
                $q->whereDate('business_date', now()->toDateString())
                    ->orWhereBetween('clock_in_time', [now()->startOfDay(), now()->endOfDay()]);
            })
            ->orderByDesc('clock_in_time')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        // 4. Get users with names.
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // 5. Get busy users (tasks in_progress or approved today).
        $busyUserIds = EmployeeTaskRequest::whereIn('user_id', $userIds)
            ->whereIn('status', ['in_progress', 'approved'])
            ->whereDate('task_date', today())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $staleMinutes = $this->locationStaleMinutes();

        // 6. Build result per user.
        $results = [];
        foreach ($userIds as $userId) {
            $user = $users->get($userId);
            if (! $user) {
                continue;
            }

            $attendance = $attendances->get($userId);

            // Primary: latest user_locations record (from track-location API).
            $latestPoint = null;
            $latestPointAt = null;
            $userLoc = $latestUserLocations->get($userId);
            if ($userLoc) {
                $latestPoint = [
                    'latitude' => $userLoc->latitude,
                    'longitude' => $userLoc->longitude,
                    'accuracy' => $userLoc->accuracy,
                    'timestamp' => $userLoc->recorded_at
                        ? $userLoc->recorded_at->setTimezone(getTimeZoneBranchByRequest())->format('Y-m-d H:i:s')
                        : null,
                    'location_source' => $userLoc->location_source ?? 'GPS',
                ];
                $latestPointAt = $userLoc->recorded_at;
            }

            // Fallback 1: attendance.location_tracking (last tracking point).
            if (! $latestPoint && $attendance && ! empty($attendance->location_tracking)) {
                $trackingData = $attendance->location_tracking;
                $tracking = end($trackingData);
                if (is_array($tracking)) {
                    $latestPoint = $tracking;
                    $latestPointAt = $this->parsePointTimestamp($tracking['timestamp'] ?? null);
                }
            }

            // Fallback 2: attendance.clock_in_location.
            if (! $latestPoint && $attendance && ! empty($attendance->clock_in_location)) {
                $latestPoint = array_merge($attendance->clock_in_location, [
                    'timestamp' => $attendance->clock_in_time
                        ? Carbon::parse($attendance->clock_in_time, $attendance->timezone ?? getTimeZoneBranchByRequest())->format('Y-m-d H:i:s')
                        : null,
                    'type' => 'clock_in',
                    'location_source' => 'clock_in',
                ]);
                $latestPointAt = $attendance->clock_in_time
                    ? Carbon::parse($attendance->clock_in_time, $attendance->timezone ?? getTimeZoneBranchByRequest())
                    : null;
            }

            $employeeLat = $latestPoint['latitude'] ?? null;
            $employeeLng = $latestPoint['longitude'] ?? null;

            $distanceMeters = null;
            if ($employeeLat !== null && $employeeLng !== null) {
                $distanceMeters = (int) round(GeoDistance::metres(
                    $notificationLat, $notificationLng,
                    (float) $employeeLat, (float) $employeeLng,
                ));
            }

            $isStaleLocation = $latestPoint !== null
                && ($latestPointAt === null || $latestPointAt->lessThan(now()->subMinutes($staleMinutes)));

            // Clock-in eligibility is resolved only for users with no clock-in today:
            // they are the ones who can still flip to absent or clock in. Users who
            // already clocked in (or are already flagged absent) never call the
            // constraint service, and each user is resolved at most once per request.
            $eligibility = ['can_clock_in' => false, 'can_clock_in_until' => null, 'absent_by_deadline' => false];
            $hasClockInToday = $attendance !== null && $attendance->clock_in_time !== null;
            if (! $hasClockInToday && ! (bool) ($attendance?->is_absent)) {
                $eligibility = $this->resolveClockInEligibility($user);
            }

            $status = $this->deriveEmployeeStatus(
                $attendance,
                $latestPoint !== null,
                in_array($userId, $busyUserIds, true),
                $isStaleLocation,
                $eligibility['absent_by_deadline'],
                $distanceMeters,
                $radiusMeters,
            );

            $results[] = [
                'user_id' => $userId,
                'name' => $user->name,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'distance_meters' => $distanceMeters,
                'distance_label' => $this->formatDistance($distanceMeters),
                'last_update' => $latestPoint['timestamp'] ?? null,
                'location' => $latestPoint ? [
                    'latitude' => $employeeLat,
                    'longitude' => $employeeLng,
                    'accuracy' => $latestPoint['accuracy'] ?? null,
                    'source' => $latestPoint['location_source'] ?? 'GPS',
                ] : null,
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'status' => $attendance->status,
                    'clock_in_time' => $attendance->clock_in_time
                        ? Carbon::parse($attendance->clock_in_time, $attendance->timezone ?? getTimeZoneBranchByRequest())->format('H:i:s')
                        : null,
                ] : null,
                'can_clock_in' => $eligibility['can_clock_in'],
                'can_clock_in_until' => $eligibility['can_clock_in_until'],
                'is_absent' => (bool) ($attendance?->is_absent) || $eligibility['absent_by_deadline'],
            ];
        }

        // 7. Status filtering. An explicit statuses[] list takes precedence; otherwise
        //    unavailable statuses (absent, out) are hidden unless include_unavailable.
        if ($statuses !== []) {
            $allowed = array_flip($statuses);
            $results = array_values(array_filter($results, fn ($r) => isset($allowed[$r['status']])));
        } elseif (! $includeUnavailable) {
            $results = array_values(array_filter($results, fn ($r) => ! in_array($r['status'], self::UNAVAILABLE_STATUSES, true)));
        }

        // 8. Sort by distance (nulls last).
        usort($results, function ($a, $b) {
            if ($a['distance_meters'] === null) {
                return 1;
            }
            if ($b['distance_meters'] === null) {
                return -1;
            }

            return $a['distance_meters'] <=> $b['distance_meters'];
        });

        // 9. Radius. With no new params the legacy behaviour is kept exactly: employees
        //    beyond the radius are dropped (null distances kept). When the caller opted
        //    into statuses[] or include_unavailable the radius only classifies — far
        //    employees stay in the payload as `available_far`.
        if ($radiusMeters !== null && $statuses === [] && ! $includeUnavailable) {
            $results = array_filter($results, fn ($r) => $r['distance_meters'] === null || $r['distance_meters'] <= $radiusMeters);
            $results = array_values($results);
        }

        return $results;
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return GeoDistance::metres($lat1, $lon1, $lat2, $lon2);
    }

    private function deriveEmployeeStatus(
        ?Attendance $attendance,
        bool $hasLocation,
        bool $isBusy,
        bool $isStaleLocation,
        bool $isAbsentByDeadline,
        ?int $distanceMeters,
        ?float $radiusMeters,
    ): string {
        // Absent: flagged on today's row, or the first-clock-in deadline passed
        // with no clock-in. Checked before out/busy/available (R9).
        if ((bool) ($attendance?->is_absent) || $isAbsentByDeadline) {
            return 'absent';
        }

        // Clocked out / completed for today.
        if ($attendance && ($attendance->clock_out_time !== null || $attendance->status === Attendance::STATUS_COMPLETED)) {
            return 'out';
        }

        if ($isBusy) {
            return 'busy';
        }

        if (! $hasLocation) {
            return $attendance ? 'no_location' : 'offline';
        }

        // A point exists but is older than the staleness threshold (or untimed).
        if ($isStaleLocation) {
            return 'not_connected';
        }

        if ($radiusMeters !== null && $distanceMeters !== null && $distanceMeters > $radiusMeters) {
            return 'available_far';
        }

        return 'available';
    }

    /**
     * Clock-in window for a user with no clock-in today, from the attendance work rules.
     *
     * Returns can_clock_in / can_clock_in_until (ISO-8601|null) / absent_by_deadline.
     * Degrades gracefully when the parallel rules work is not deployed (missing keys,
     * no periods, resolver failure): can_clock_in=true, no deadline, no absence.
     *
     * @return array{can_clock_in: bool, can_clock_in_until: ?string, absent_by_deadline: bool}
     */
    private function resolveClockInEligibility(User $user): array
    {
        $fallback = ['can_clock_in' => true, 'can_clock_in_until' => null, 'absent_by_deadline' => false];

        try {
            $rules = $this->attendanceConstraintService->getTodaysWorkRulesForUser($user, now());
        } catch (\Throwable) {
            return $fallback;
        }

        // The rules live at the top level of the resolver response today, and move
        // under a `work_rules` envelope in the V2 contract — accept both shapes.
        $workRules = is_array($rules['work_rules'] ?? null) ? $rules['work_rules'] : $rules;

        $periods = $workRules['all_work_periods'] ?? [];
        if (! is_array($periods) || $periods === []) {
            return $fallback;
        }

        $earlyClockInRules = $workRules['early_clock_in_rules'] ?? null;
        $earlyMinutes = EarlyClockInRules::minutes(is_array($earlyClockInRules) ? $earlyClockInRules : null);

        $extensionRules = $workRules['extension_rules'] ?? null;
        $extensionMinutes = is_array($extensionRules)
            ? max(0, (int) round(((float) ($extensionRules['extension_hours'] ?? 0)) * 60))
            : 0;

        $deadlineRules = $workRules['clock_in_deadline_rules'] ?? null;
        $canClockInBeforeMinutes = is_array($deadlineRules) && isset($deadlineRules['can_clock_in_before_minutes'])
            ? max(0, (int) $deadlineRules['can_clock_in_before_minutes'])
            : null;

        // One window per scheduled period (all boundaries from ShiftWindowCalculator).
        $windows = [];
        foreach ($periods as $period) {
            $periodStart = $period['period_start_time_carbon'] ?? null;
            $periodEnd = $period['period_end_time_carbon'] ?? null;
            if (! $periodStart instanceof \Carbon\CarbonInterface || ! $periodEnd instanceof \Carbon\CarbonInterface) {
                continue;
            }

            $windows[] = $this->shiftWindowCalculator->compute(new ShiftWindowInput(
                scheduledStart: CarbonImmutable::instance($periodStart),
                scheduledEnd: CarbonImmutable::instance($periodEnd),
                clockIn: null,
                earlyWindowMinutes: $earlyMinutes,
                extensionMinutes: $extensionMinutes,
                canClockInBeforeMinutes: $canClockInBeforeMinutes,
            ));
        }

        if ($windows === []) {
            return $fallback;
        }

        // Relevant period: the one whose ordinary window is still open at now (covers
        // "contains now", the extension tail, and the next upcoming period). When every
        // window is fully past there is nothing left to clock into.
        $now = CarbonImmutable::instance(now());
        $relevant = null;
        foreach ($windows as $window) {
            if ($now->lessThanOrEqualTo($window->workWindowEnd)) {
                $relevant = $window;
                break;
            }
        }

        // Deadline absence: the day's last first-clock-in deadline passed with no clock-in.
        $lastWindow = end($windows);
        $absentByDeadline = $lastWindow->firstClockInDeadline !== null
            && $now->greaterThan($lastWindow->firstClockInDeadline);

        if ($relevant === null) {
            return ['can_clock_in' => false, 'can_clock_in_until' => null, 'absent_by_deadline' => $absentByDeadline];
        }

        $clockInUntil = $relevant->firstClockInDeadline ?? $relevant->workWindowEnd;
        $canClockIn = $now->greaterThanOrEqualTo($relevant->workWindowStart)
            && $now->lessThanOrEqualTo($clockInUntil);

        return [
            'can_clock_in' => $canClockIn,
            'can_clock_in_until' => $clockInUntil->toIso8601String(),
            'absent_by_deadline' => $absentByDeadline,
        ];
    }

    private function locationStaleMinutes(): int
    {
        return max(1, (int) config('projectmanagement.notifications.location_stale_minutes', self::LOCATION_STALE_MINUTES));
    }

    private function parsePointTimestamp(?string $timestamp): ?Carbon
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Throwable) {
            return null;
        }
    }

    private function statusLabel(string $status): string
    {
        $locale = app()->getLocale();

        $labels = [
            'available' => ['ar' => 'متاح', 'en' => 'Available'],
            'busy' => ['ar' => 'مشغول', 'en' => 'Busy'],
            'offline' => ['ar' => 'غير متصل', 'en' => 'Offline'],
            'no_location' => ['ar' => 'لا يوجد موقع', 'en' => 'No Location'],
            'available_far' => ['ar' => 'متاح بعيد', 'en' => 'Available Far'],
            'not_connected' => ['ar' => 'لا يوجد تحديث', 'en' => 'Not Connected'],
            'out' => ['ar' => 'خارج', 'en' => 'Out'],
            'absent' => ['ar' => 'غائب', 'en' => 'Absent'],
        ];

        return $labels[$status][$locale] ?? $status;
    }

    private function formatDistance(?int $meters): ?string
    {
        if ($meters === null) {
            return null;
        }

        $locale = app()->getLocale();

        if ($meters >= 1000) {
            $km = round($meters / 1000, 1);

            return $locale === 'ar' ? "{$km} كم" : "{$km} km";
        }

        return $locale === 'ar' ? "{$meters} م" : "{$meters} m";
    }
}

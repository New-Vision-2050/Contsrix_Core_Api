<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\UserLocation;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\User\Models\User;
use Throwable;

class ProjectNotificationLocationService
{
    /**
     * Slim attendance projection. Never select location_tracking / verification_data /
     * overtime_flags / business_date here: those JSON/date casts OOM or throw on
     * a single bad production row and 500 the whole employees-with-locations list.
     *
     * @var list<string>
     */
    private const ATTENDANCE_LIST_COLUMNS = [
        'attendances.id',
        'attendances.user_id',
        'attendances.status',
        'attendances.clock_in_time',
        'attendances.clock_out_time',
        'attendances.timezone',
        'attendances.clock_in_location',
    ];

    public function getProjectEmployeesWithLocations(
        string $projectId,
        float $notificationLat,
        float $notificationLng,
        ?float $radiusMeters = null,
    ): array {
        \Log::info('DEBUG: Starting getProjectEmployeesWithLocations', ['project_id' => $projectId]);
        
        $companyId = (string) tenant('id');
        \Log::info('DEBUG: Got tenant ID', ['company_id' => $companyId]);

        // 1. Get user IDs assigned to the project.
        \Log::info('DEBUG: Querying ProjectEmployee');
        $userIds = ProjectEmployee::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();
        
        \Log::info('DEBUG: Got user IDs', ['count' => $userIds->count()]);

        if ($userIds->isEmpty()) {
            \Log::info('DEBUG: No users found, returning empty array');
            return [];
        }

        // 2. Get the latest user_locations record per user (no date filter).
        //    The track-location API always writes to user_locations, even when
        //    the user has active attendance, so this is the most reliable source.
        //    Note: id is a UUID, so MAX(id) is meaningless; order by recorded_at.
        \Log::info('DEBUG: Querying latestUserLocationsByUserId');
        $latestUserLocations = $this->latestUserLocationsByUserId($userIds);
        \Log::info('DEBUG: Got latestUserLocations', ['count' => $latestUserLocations->count()]);

        // 3. Batch-query the latest attendance per user for today (for status).
        \Log::info('DEBUG: Querying latestAttendancesByUserId');
        $attendances = $this->latestAttendancesByUserId($userIds);
        \Log::info('DEBUG: Got attendances', ['count' => $attendances->count()]);

        // 4. Get users with names.
        \Log::info('DEBUG: Querying User model');
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        \Log::info('DEBUG: Got users', ['count' => $users->count()]);

        // 5. Get busy users (tasks in_progress or approved today).
        \Log::info('DEBUG: Querying EmployeeTaskRequest');
        $busyUserIds = EmployeeTaskRequest::whereIn('user_id', $userIds)
            ->whereIn('status', ['in_progress', 'approved'])
            ->whereDate('task_date', today())
            ->pluck('user_id')
            ->unique()
            ->toArray();
        \Log::info('DEBUG: Got busy user IDs', ['count' => count($busyUserIds)]);

        \Log::info('DEBUG: Calling locationTrackingFallbackByUserId');
        $trackingFallbackByUserId = $this->locationTrackingFallbackByUserId(
            $userIds,
            $latestUserLocations,
            $attendances,
        );
        \Log::info('DEBUG: Got tracking fallback', ['count' => count($trackingFallbackByUserId)]);

        // 6. Build result per user.
        $results = [];
        foreach ($userIds as $userId) {
            $user = $users->get($userId);
            if (! $user) {
                continue;
            }

            $attendance = $attendances->get($userId);
            $latestPoint = $this->resolveLatestPoint(
                $latestUserLocations->get($userId),
                $trackingFallbackByUserId[$userId] ?? null,
                $attendance,
            );

            $employeeLat = $latestPoint['latitude'] ?? null;
            $employeeLng = $latestPoint['longitude'] ?? null;

            $distanceMeters = null;
            if ($employeeLat !== null && $employeeLng !== null) {
                $distanceMeters = (int) round(GeoDistance::metres(
                    $notificationLat, $notificationLng,
                    (float) $employeeLat, (float) $employeeLng,
                ));
            }

            $status = $this->deriveEmployeeStatus(
                $attendance,
                $latestPoint !== null,
                in_array($userId, $busyUserIds, true),
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
                    'clock_in_time' => $this->formatClockInTime(
                        $attendance->clock_in_time,
                        $attendance->timezone,
                    ),
                ] : null,
            ];
        }

        // 7. Sort by distance (nulls last).
        usort($results, function ($a, $b) {
            if ($a['distance_meters'] === null) {
                return 1;
            }
            if ($b['distance_meters'] === null) {
                return -1;
            }

            return $a['distance_meters'] <=> $b['distance_meters'];
        });

        // 8. Filter by radius if provided.
        if ($radiusMeters !== null) {
            $results = array_filter($results, fn ($r) => $r['distance_meters'] === null || $r['distance_meters'] <= $radiusMeters);
            $results = array_values($results);
        }

        \Log::info('DEBUG: Returning results', ['count' => count($results)]);
        return $results;
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return GeoDistance::metres($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<string, UserLocation>
     */
    private function latestUserLocationsByUserId(Collection $userIds): Collection
    {
        // Optimized: Use a simple approach instead of complex join
        // Get all locations for these users, ordered by recorded_at DESC
        $allLocations = UserLocation::whereIn('user_id', $userIds)
            ->orderByDesc('recorded_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        // Group by user_id and take the first (latest) for each user
        return $allLocations->groupBy('user_id')
            ->map(fn ($locations) => $locations->first())
            ->filter();
    }

    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<string, Attendance>
     */
    private function latestAttendancesByUserId(Collection $userIds): Collection
    {
        $latestAttendanceSubquery = Attendance::whereIn('user_id', $userIds)
            ->whereBetween('clock_in_time', [now()->startOfDay(), now()->endOfDay()])
            ->where('is_absent', false)
            ->where('is_holiday', false)
            ->select('user_id', DB::raw('MAX(clock_in_time) as latest_clock_in'))
            ->groupBy('user_id');

        try {
            return Attendance::joinSub($latestAttendanceSubquery, 'latest_attendance', function ($join) {
                $join->on('attendances.user_id', '=', 'latest_attendance.user_id')
                    ->on('attendances.clock_in_time', '=', 'latest_attendance.latest_clock_in');
            })
                ->select(self::ATTENDANCE_LIST_COLUMNS)
                ->orderByDesc('attendances.clock_in_time')
                ->orderByDesc('attendances.created_at')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');
        } catch (Throwable) {
            // A single corrupt clock_in_location JSON must not 500 the list.
            return Attendance::joinSub($latestAttendanceSubquery, 'latest_attendance', function ($join) {
                $join->on('attendances.user_id', '=', 'latest_attendance.user_id')
                    ->on('attendances.clock_in_time', '=', 'latest_attendance.latest_clock_in');
            })
                ->select([
                    'attendances.id',
                    'attendances.user_id',
                    'attendances.status',
                    'attendances.clock_in_time',
                    'attendances.clock_out_time',
                    'attendances.timezone',
                ])
                ->orderByDesc('attendances.clock_in_time')
                ->orderByDesc('attendances.created_at')
                ->get()
                ->unique('user_id')
                ->keyBy('user_id');
        }
    }

    /**
     * Load location_tracking only for employees who have no user_locations row.
     * Production track-location always writes user_locations, so this stays empty there.
     *
     * @param  Collection<int, string>  $userIds
     * @param  Collection<string, UserLocation>  $latestUserLocations
     * @param  Collection<string, Attendance>  $attendances
     * @return array<string, array<string, mixed>>
     */
    private function locationTrackingFallbackByUserId(
        Collection $userIds,
        Collection $latestUserLocations,
        Collection $attendances,
    ): array {
        $attendanceIds = [];
        foreach ($userIds as $userId) {
            if ($latestUserLocations->has($userId)) {
                continue;
            }

            $attendance = $attendances->get($userId);
            if ($attendance?->id) {
                $attendanceIds[] = $attendance->id;
            }
        }

        if ($attendanceIds === []) {
            return [];
        }

        try {
            $rows = Attendance::query()
                ->whereIn('id', $attendanceIds)
                ->select('id', 'user_id', 'location_tracking')
                ->get();
        } catch (Throwable) {
            return [];
        }

        $points = [];
        foreach ($rows as $row) {
            try {
                $tracking = $row->location_tracking;
                if (! is_array($tracking) || $tracking === []) {
                    continue;
                }

                $last = end($tracking);
                if (is_array($last)) {
                    $points[(string) $row->user_id] = $last;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $points;
    }

    /**
     * @param  array<string, mixed>|null  $trackingPoint
     * @return array<string, mixed>|null
     */
    private function resolveLatestPoint(
        ?UserLocation $userLoc,
        ?array $trackingPoint,
        ?Attendance $attendance,
    ): ?array {
        if ($userLoc) {
            return [
                'latitude' => $userLoc->latitude,
                'longitude' => $userLoc->longitude,
                'accuracy' => $userLoc->accuracy,
                'timestamp' => $this->formatRecordedAt($userLoc->recorded_at),
                'location_source' => $userLoc->location_source ?? 'GPS',
            ];
        }

        if (is_array($trackingPoint)) {
            return $trackingPoint;
        }

        if ($attendance && ! empty($attendance->clock_in_location) && is_array($attendance->clock_in_location)) {
            return array_merge($attendance->clock_in_location, [
                'timestamp' => $this->formatStoredDateTime(
                    $attendance->clock_in_time,
                    $attendance->timezone,
                ),
                'type' => 'clock_in',
                'location_source' => 'clock_in',
            ]);
        }

        return null;
    }

    private function formatRecordedAt(mixed $recordedAt): ?string
    {
        if ($recordedAt === null || $recordedAt === '') {
            return null;
        }

        try {
            $carbon = $recordedAt instanceof Carbon
                ? $recordedAt->copy()
                : Carbon::parse($recordedAt);

            return $carbon->format('Y-m-d H:i:s');
        } catch (Throwable) {
            if ($recordedAt instanceof \DateTimeInterface) {
                return $recordedAt->format('Y-m-d H:i:s');
            }

            return is_string($recordedAt) ? $recordedAt : null;
        }
    }

    private function formatClockInTime(mixed $clockInTime, mixed $timezone): ?string
    {
        $formatted = $this->formatStoredDateTime($clockInTime, $timezone);

        if ($formatted === null) {
            return null;
        }

        try {
            return Carbon::parse($formatted)->format('H:i:s');
        } catch (Throwable) {
            return $formatted;
        }
    }

    private function formatStoredDateTime(mixed $value, mixed $timezone = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $this->safeTimezone(is_string($timezone) ? $timezone : null))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (Throwable) {
                return is_string($value) ? $value : null;
            }
        }
    }

    private function safeTimezone(?string $timezone = null): string
    {
        $candidates = [
            $timezone,
            getTimeZoneBranchByRequest(),
            config('app.timezone'),
            'Asia/Riyadh',
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || $candidate === '') {
                continue;
            }

            try {
                new \DateTimeZone($candidate);

                return $candidate;
            } catch (Throwable) {
                continue;
            }
        }

        return 'UTC';
    }

    private function deriveEmployeeStatus(
        ?Attendance $attendance,
        bool $hasLocation,
        bool $isBusy,
    ): string {
        if (! $attendance) {
            if ($isBusy) {
                return 'busy';
            }

            return $hasLocation ? 'available' : 'offline';
        }

        // Clocked out / completed for today.
        if ($attendance->clock_out_time !== null || $attendance->status === Attendance::STATUS_COMPLETED) {
            return 'out';
        }

        if ($isBusy) {
            return 'busy';
        }

        if (! $hasLocation) {
            return 'no_location';
        }

        return 'available';
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

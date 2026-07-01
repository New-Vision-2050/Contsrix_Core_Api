<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\UserLocation;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\User\Models\User;

class ProjectNotificationLocationService
{
    public function getProjectEmployeesWithLocations(
        string $projectId,
        float $notificationLat,
        float $notificationLng,
        ?float $radiusMeters = null,
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
        $latestLocationIds = UserLocation::whereIn('user_id', $userIds)
            ->selectRaw('user_id, MAX(id) as max_id')
            ->groupBy('user_id')
            ->pluck('max_id');

        $latestUserLocations = UserLocation::whereIn('id', $latestLocationIds)
            ->get()
            ->keyBy('user_id');

        // 3. Batch-query the latest attendance per user for today (for status).
        $latestAttendanceSubquery = Attendance::whereIn('user_id', $userIds)
            ->whereBetween('clock_in_time', [now()->startOfDay(), now()->endOfDay()])
            ->where('is_absent', false)
            ->where('is_holiday', false)
            ->select('user_id', \DB::raw('MAX(clock_in_time) as latest_clock_in'))
            ->groupBy('user_id');

        $attendances = Attendance::joinSub($latestAttendanceSubquery, 'latest_attendance', function ($join) {
            $join->on('attendances.user_id', '=', 'latest_attendance.user_id')
                ->on('attendances.clock_in_time', '=', 'latest_attendance.latest_clock_in');
        })
            ->get()
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
            $userLoc = $latestUserLocations->get($userId);
            if ($userLoc) {
                $latestPoint = [
                    'latitude' => $userLoc->latitude,
                    'longitude' => $userLoc->longitude,
                    'accuracy' => $userLoc->accuracy,
                    'timestamp' => $userLoc->recorded_at?->format('Y-m-d H:i:s'),
                    'location_source' => $userLoc->location_source ?? 'GPS',
                ];
            }

            // Fallback 1: attendance.location_tracking (last tracking point).
            if (! $latestPoint && $attendance && ! empty($attendance->location_tracking)) {
                $trackingData = $attendance->location_tracking;
                $tracking = end($trackingData);
                if (is_array($tracking)) {
                    $latestPoint = $tracking;
                }
            }

            // Fallback 2: attendance.clock_in_location.
            if (! $latestPoint && $attendance && ! empty($attendance->clock_in_location)) {
                $latestPoint = array_merge($attendance->clock_in_location, [
                    'timestamp' => $attendance->clock_in_time ? Carbon::parse($attendance->clock_in_time)->format('Y-m-d H:i:s') : null,
                    'type' => 'clock_in',
                    'location_source' => 'clock_in',
                ]);
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

            $status = $this->deriveEmployeeStatus(
                $attendance,
                $latestPoint !== null,
                in_array($userId, $busyUserIds, true),
                $latestPoint['timestamp'] ?? null,
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
                    'clock_in_time' => $attendance->clock_in_time ? Carbon::parse($attendance->clock_in_time)->format('H:i:s') : null,
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
        ?string $lastUpdateTimestamp,
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

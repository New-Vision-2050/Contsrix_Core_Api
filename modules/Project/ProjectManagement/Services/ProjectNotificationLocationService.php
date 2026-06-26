<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Attendance\Models\Attendance;
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
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        // 2. Batch-query today's attendances for ALL project employees at once.
        $attendances = Attendance::whereIn('user_id', $userIds)
            ->whereBetween('clock_in_time', [now()->startOfDay(), now()->endOfDay()])
            ->where('is_absent', false)
            ->where('is_holiday', false)
            ->orderByDesc('clock_in_time')
            ->get()
            ->groupBy('user_id');

        // 3. Get users with names.
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        // 4. Get busy users (tasks in_progress or approved today).
        $busyUserIds = EmployeeTaskRequest::whereIn('user_id', $userIds)
            ->whereIn('status', ['in_progress', 'approved'])
            ->whereDate('task_date', today())
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // 5. Build result per user.
        $results = [];
        foreach ($userIds as $userId) {
            $user = $users->get($userId);
            if (!$user) {
                continue;
            }

            $userAttendances = $attendances->get($userId);
            $attendance = $userAttendances?->first();

            $latestPoint = null;
            if ($attendance && !empty($attendance->location_tracking)) {
                $tracking = collect($attendance->location_tracking)
                    ->sortByDesc(fn($p) => strtotime($p['timestamp'] ?? 'now'))
                    ->first();
                if ($tracking) {
                    $latestPoint = $tracking;
                }
            }

            // Fallback to clock-in location.
            if (!$latestPoint && $attendance && !empty($attendance->clock_in_location)) {
                $latestPoint = array_merge($attendance->clock_in_location, [
                    'timestamp' => $attendance->clock_in_time?->format('Y-m-d H:i:s'),
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
                $attendance !== null,
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
                    'clock_in_time' => $attendance->clock_in_time?->format('H:i:s'),
                ] : null,
            ];
        }

        // 6. Sort by distance (nulls last).
        usort($results, function ($a, $b) {
            if ($a['distance_meters'] === null) return 1;
            if ($b['distance_meters'] === null) return -1;
            return $a['distance_meters'] <=> $b['distance_meters'];
        });

        // 7. Filter by radius if provided.
        if ($radiusMeters !== null) {
            $results = array_filter($results, fn($r) => $r['distance_meters'] === null || $r['distance_meters'] <= $radiusMeters);
            $results = array_values($results);
        }

        return $results;
    }

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return GeoDistance::metres($lat1, $lon1, $lat2, $lon2);
    }

    private function deriveEmployeeStatus(
        bool $hasAttendance,
        bool $hasLocation,
        bool $isBusy,
        ?string $lastUpdateTimestamp,
    ): string {
        if (!$hasAttendance) {
            return 'offline';
        }

        if ($isBusy) {
            return 'busy';
        }

        if (!$hasLocation) {
            return 'no_location';
        }

        // Check freshness: < 15 min = available.
        if ($lastUpdateTimestamp) {
            $minutesAgo = now()->parse($lastUpdateTimestamp)->diffInMinutes(now());
            if ($minutesAgo > 15) {
                return 'no_location';
            }
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

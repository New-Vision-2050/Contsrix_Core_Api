<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\Attendance;

class LiveTrackingPresenter extends AbstractPresenter
{
    public function __construct(private Attendance $attendance)
    {
    }

    public function present(bool $isListing = false): array
    {
        // Get all tracking points, or an empty array if none.
        $allTrackingPoints = $this->attendance->location_tracking ?? [];

        // Get last 4 tracking points in descending order
        $trackingPoints = array_slice(array_reverse($allTrackingPoints), 0, 4);

        // Find the most recent tracking point (first in reversed array)
        $latestPoint = !empty($trackingPoints) ? $trackingPoints[0] : null;

        return [
            'attendance_id' => $this->attendance->id,
            'user' => $this->attendance->user ? [
                'id' => $this->attendance->user->id ?? '-',
                'name' => $this->attendance->user->name ?? '-',
                'email' => $this->attendance->user->email ?? '-',
                'phone' => $this->attendance->user->phone ?? '-',
                'company_name' => $this->attendance->user->company->name ?? '-',
                'country' => $this->attendance->user->companyUser->country?->name ?? '-',
                'birthdate' => $this->attendance->user->companyUser->birthdate_gregorian ?? '-',
                'gender' => __('validation.' . $this->attendance->user->companyUser->gender) ?? '-',

                'branch_name'   => $this->attendance->user->professionalData->branch->name ?? '-',
                'department_name' => $this->attendance->user->professionalData->department->name ?? '-',
                'management_name' => $this->attendance->user->professionalData->management->name ?? '-',
            ] : null,

            'clock_in_time' => $this->attendance->clock_in_time->format('H:i:s'),

            'status' => $this->attendance->status,
            'is_late' => (int) $this->attendance->is_late,
            'is_absent' => (int) $this->attendance->is_absent,
            'is_holiday' => (int) $this->attendance->is_holiday,

            // --- Latest Location Info (for the marker) ---
            'latest_location' => $latestPoint ? [
                'latitude'  => (float) $latestPoint['latitude'],
                'longitude' => (float) $latestPoint['longitude'],
                'timestamp' => $latestPoint['timestamp'],
                'accuracy'  => (float) ($latestPoint['accuracy'] ?? 5.0),
                'type' => $latestPoint['type'] ?? 'track',
                'is_mock' => $latestPoint['is_mock'] ?? false,
                'device_id' => $latestPoint['device_id'] ?? 'unknown',
                'location_source' => $latestPoint['location_source'] ?? 'GPS',
            ] : [
                'latitude'  => $this->attendance->clock_in_location['latitude'] ?? 0,
                'longitude' => $this->attendance->clock_in_location['longitude'] ?? 0,
                'timestamp' => $this->attendance->clock_in_time->format('Y-m-d H:i:s'),
                'accuracy'  => 10,
                'type' => 'clock_in',
                'is_mock' => false,
                'device_id' => 'unknown',
                'location_source' => 'GPS',
            ],

            // --- All tracking points with enhanced data ---
            'tracking_points' => array_map(function ($point) {
                return [
                    'latitude' => (float) $point['latitude'],
                    'longitude' => (float) $point['longitude'],
                    'timestamp' => $point['timestamp'],
                    'accuracy' => (float) ($point['accuracy'] ?? 5.0),
                    'type' => $point['type'] ?? 'track',
                    'is_mock' => $point['is_mock'] ?? false,
                    'device_id' => $point['device_id'] ?? 'unknown',
                    'location_source' => $point['location_source'] ?? 'GPS',
                    'gps_status' => $point['gps_status'] ?? null,
                    'action' => $point['action'] ?? null,
                    'geofence_id' => $point['geofence_id'] ?? null,
                    'processed_at' => $point['processed_at'] ?? null,
                ];
            }, $trackingPoints),

            // --- Simple tracking path for map display ---
            'tracking_path' => array_map(function ($point) {
                return [
                    'lat' => (float) $point['latitude'],
                    'lng' => (float) $point['longitude'],
                    'timestamp' => $point['timestamp'],
                    'type' => $point['type'] ?? 'track',
                ];
            }, $trackingPoints),

            // --- Statistics ---
            'tracking_stats' => [
                'total_points' => count($trackingPoints),
                'track_points' => count(array_filter($trackingPoints, fn($p) => ($p['type'] ?? 'track') === 'track')),
                'geofence_points' => count(array_filter($trackingPoints, fn($p) => ($p['type'] ?? 'track') === 'geofence')),
                'mock_locations' => count(array_filter($trackingPoints, fn($p) => ($p['is_mock'] ?? false) === true)),
                'last_update' => $latestPoint['timestamp'] ?? $this->attendance->clock_in_time->format('Y-m-d H:i:s'),
            ],
        ];
    }
}

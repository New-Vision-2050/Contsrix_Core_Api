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
        $trackingPoints = $this->attendance->location_tracking ?? [];

        // Find the most recent tracking point.
        $latestPoint = !empty($trackingPoints) ? end($trackingPoints) : null;

        return [
            'attendance_id' => $this->attendance->id,
            'employee_name' => $this->attendance->user->name ?? 'N/A',
            'branch_name'   => $this->attendance->user->branch->name ?? 'N/A',
            // 'department_name' => 'إدارة الموارد البشرية',
            'clock_in_time' => $this->attendance->clock_in_time->format('H:i:s'),

            // --- Latest Location Info (for the marker) ---
            'latest_location' => $latestPoint ? [
                'latitude'  => (float) $latestPoint['latitude'],
                'longitude' => (float) $latestPoint['longitude'],
                'timestamp' => $latestPoint['timestamp'],
                'accuracy'  => (float) $latestPoint['accuracy'],
            ] : [
                'latitude'  => $this->attendance->clock_in_location['latitude'],
                'longitude' => $this->attendance->clock_in_location['longitude'],
                'timestamp' => $this->attendance->clock_in_time->format('Y-m-d H:i:s'),
                 'accuracy'  => 10,
            ],

            'tracking_path' => array_map(function ($point) {
                return [
                    'lat' => (float) $point['latitude'],
                    'lng' => (float) $point['longitude'],
                ];
            }, $trackingPoints),
        ];
    }
}

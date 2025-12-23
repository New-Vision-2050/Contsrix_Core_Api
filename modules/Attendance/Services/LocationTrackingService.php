<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\Attendance;

class LocationTrackingService
{
    public function __construct(
        private AttendanceConstraintService $constraintService
    ) {
    }

    /**
     * Appends new tracking points to an existing attendance record.
     *
     * @param Attendance $attendance The attendance record to update.
     * @param array $newPoints An array of LocationTrackingPoint data objects or enhanced arrays.
     */
    public function addTrackingPoints(Attendance $attendance, array $newPoints): void
    {
        // Get existing tracking data, or an empty array if none exists.
        $existingTracking = $attendance->location_tracking ?? [];
        
        // Convert the new LocationTrackingPoint objects to arrays.

        $newPointsAsArray = array_map(function($point) {
            if ($point instanceof \Modules\Attendance\DataClasses\LocationTrackingPoint) {
                return $point->toArray();
            }
            // If it's already an array (enhanced tracking point), return as is
            return $point;
        }, $newPoints);

        // Merge the old and new tracking points.
        $mergedTracking = array_merge($existingTracking, $newPointsAsArray);

        // Sort the merged array by timestamp to ensure chronological order.
        usort($mergedTracking, fn($a, $b) => strtotime($a['timestamp']) <=> strtotime($b['timestamp']));

        // Update the attendance record with the new, complete tracking history.
        $attendance->location_tracking = $mergedTracking;
        $attendance->save();
    }

    /**
     * (Optional) Triggers a validation check for the attendance record.
     * This is useful for real-time radius enforcement.
     */
    public function checkForViolations(Attendance $attendance): void
    {
        // The requestData can be empty if not needed by your constraint services.
        $this->constraintService->validateAttendance($attendance, []);
    }

     /**
     * Fetches all active attendance records for the current day.
     *
     * @param array $filters (Optional) Filters to apply, e.g., by branch_id.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodaysActiveAttendance(array $filters = [])
    {
        // Get the start and end of the current day based on the app's timezone.
    $startOfDay = now()->startOfDay();
    $endOfDay = now()->endOfDay();

    $subQuery = Attendance::whereBetween('clock_in_time', [$startOfDay, $endOfDay])
        ->where('is_absent', false)
        ->where('is_holiday', false)
        ->filter($filters)
        ->select('user_id', \DB::raw('MAX(clock_in_time) as latest_clock_in'))
        ->groupBy('user_id');

    return Attendance::joinSub($subQuery, 'latest_attendance', function ($join) {
            $join->on('attendances.user_id', '=', 'latest_attendance.user_id')
                 ->on('attendances.clock_in_time', '=', 'latest_attendance.latest_clock_in');
        })
        ->with([
            'user.company',
            'user.companyUser.country',
            'user.professionalData.branch',
            'user.professionalData.department',
            'user.professionalData.management',
            'company'
        ])
        ->get();
    }

    /**
     * Handle geofence events (enter/exit)
     *
     * @param Attendance $attendance
     * @param \Modules\Attendance\DataClasses\LocationTrackingPoint $trackingPoint
     * @param string $action
     * @param string $geofenceId
     */
    public function handleGeofenceEvent(Attendance $attendance, $trackingPoint, string $action, string $geofenceId): void
    {
        // Get existing geofence events, or an empty array if none exists
        $existingGeofenceEvents = $attendance->geofence_events ?? [];
        
        // Create new geofence event
        $geofenceEvent = [
            'geofence_id' => $geofenceId,
            'action' => $action, // enter or exit
            'latitude' => $trackingPoint->latitude,
            'longitude' => $trackingPoint->longitude,
            'timestamp' => $trackingPoint->timestamp,
            'created_at' => now()->toISOString()
        ];
        
        // Add the new event
        $existingGeofenceEvents[] = $geofenceEvent;
        
        // Update the attendance record
        $attendance->geofence_events = $existingGeofenceEvents;
        $attendance->save();
        
        // Also add as a regular tracking point
        $this->addTrackingPoints($attendance, [$trackingPoint]);
    }

    public function getTodayLastAttendancePerUser()
    {

    }
}

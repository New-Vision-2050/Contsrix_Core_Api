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
     * @param array $newPoints An array of LocationTrackingPoint data objects.
     */
    public function addTrackingPoints(Attendance $attendance, array $newPoints): void
    {
        // Get existing tracking data, or an empty array if none exists.
        $existingTracking = $attendance->location_tracking ?? [];

        // Convert the new LocationTrackingPoint objects to arrays.
        $newPointsAsArray = array_map(fn($point) => $point->toArray(), $newPoints);

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
        $startOfDay = Carbon::now()->startOfDay();
        $endOfDay = Carbon::now()->endOfDay();

        $query = Attendance::query()
            ->with(['user.branch', 'company'])
            ->where('status', Attendance::STATUS_ACTIVE)
            ->whereBetween('clock_in_time', [$startOfDay, $endOfDay]);

        if (!empty($filters['branch_id'])) {
            $query->whereHas('user.branch', function ($q) use ($filters) {
                $q->where('id', $filters['branch_id']);
            });
        }

        return $query->get();
    }
}

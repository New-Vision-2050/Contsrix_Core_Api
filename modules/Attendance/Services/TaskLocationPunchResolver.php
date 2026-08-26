<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Support\GeofenceMatch;
use Modules\User\Models\User;

/**
 * Decides whether a punch was taken at a task site rather than at one of the employee's
 * own work locations, and returns the task it belongs to.
 *
 * Geofence validation only answers "inside any allowed circle" and throws the identity
 * away, so this has to run while the punch is being written: an active task's geofence
 * disappears once its window closes and cannot be reconstructed afterwards (INV-20).
 *
 * A constraint or additional location wins over a task geofence when the two overlap —
 * an employee standing in their own office is at work, whatever task they also hold.
 */
class TaskLocationPunchResolver
{
    public function __construct(
        private readonly AttendanceConstraintService $constraintService,
    ) {}

    /**
     * The task whose temporary geofence contains these coordinates, or null when they fall
     * in a constraint / additional location, in no task geofence, or are missing entirely.
     *
     * @param  array<string, mixed>|null  $coordinates  `clock_in_location` / `clock_out_location` shape
     */
    public function taskIdFor(?User $user, ?array $coordinates): ?string
    {
        if ($user === null) {
            return null;
        }

        $latitude = $coordinates['latitude'] ?? null;
        $longitude = $coordinates['longitude'] ?? null;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        try {
            $locations = $this->constraintService->clockInLocationsByKindForUser($user);
        } catch (\Throwable) {
            // Never let location bookkeeping break a punch — the row is still valid
            // attendance, it just will not be attributed to a task.
            return null;
        }

        if ($locations['task'] === []) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if (GeofenceMatch::first($latitude, $longitude, $locations['constraint']) !== null) {
            return null;
        }

        $taskId = GeofenceMatch::first($latitude, $longitude, $locations['task'])['reference_id'] ?? null;

        return is_string($taskId) && $taskId !== '' ? $taskId : null;
    }
}

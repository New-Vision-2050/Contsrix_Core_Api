<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Exceptions\AttendanceException;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Support\ClockOutGeofence;
use Modules\User\Models\User;

/**
 * Hard geofence for manual clock-out (config attendance.clock_out_geofence_enabled,
 * default on). The punch is rejected with 422 unless the request GPS falls inside
 * the same merged allowed list the app sees in user-constraint/today
 * (location_work + additional_locations, including task geofences).
 *
 * Exemptions mirror the out-of-zone auto clock-out sweep: no locations configured
 * → nothing to enforce; field work that day (accepted task / project notification)
 * → allowed from anywhere.
 *
 * Stateless — safe as an Octane singleton.
 */
class ClockOutLocationGuard
{
    public function __construct(
        private readonly AttendanceConstraintService $constraintService,
        private readonly FieldWorkOutOfZoneExemption $fieldWorkExemption,
    ) {}

    /**
     * @param array<string, mixed>|null $requestLocation clock-out request location
     *
     * @throws AttendanceException 422 when the punch is missing GPS or is outside
     *                             every allowed location
     */
    public function assertAllowed(User $user, Attendance $attendance, ?array $requestLocation): void
    {
        if (! (bool) config('attendance.clock_out_geofence_enabled', true)) {
            return;
        }

        $violation = ClockOutGeofence::violation(
            $this->allowedLocationsFor($user),
            $requestLocation,
            $this->fieldWorkExemption->appliesTo($attendance),
        );

        if ($violation === ClockOutGeofence::MISSING_LOCATION) {
            throw new AttendanceException(
                'Your location is required to clock out. Please enable GPS and try again.',
                422
            );
        }

        if ($violation === ClockOutGeofence::OUTSIDE_ALLOWED) {
            throw new AttendanceException(
                'You are outside all allowed work locations. Move into an allowed location to clock out.',
                422
            );
        }
    }

    /**
     * The merged allowed geofences for the employee: every applicable constraint's
     * branch_locations plus the additional/task locations — the same union
     * validateSingleConstraint enforces and user-constraint/today exposes.
     *
     * @return list<array<string, mixed>>
     */
    private function allowedLocationsFor(User $user): array
    {
        $constraints = $this->constraintService->getApplicableConstraints($user);
        $main = $constraints->first();

        if (! $main instanceof AttendanceConstraint) {
            return [];
        }

        $allowed = [];
        foreach ($constraints as $constraint) {
            foreach ($constraint->branch_locations ?? [] as $location) {
                if (is_array($location)) {
                    $allowed[] = $location;
                }
            }
        }

        // additionalLocationsForUser already unions table locations across all
        // applicable constraints plus the user's task geofences.
        foreach ($this->constraintService->additionalLocationsForUser($user, $main) as $location) {
            if (is_array($location)) {
                $allowed[] = $location;
            }
        }

        return $allowed;
    }
}

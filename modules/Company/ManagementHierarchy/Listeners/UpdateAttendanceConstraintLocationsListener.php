<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Listeners;

use Modules\Company\ManagementHierarchy\Events\BranchLocationUpdatedEvent;
use Modules\Attendance\Models\AttendanceConstraint;

class UpdateAttendanceConstraintLocationsListener
{
    /**
     * Handle the event.
     *
     * @param BranchLocationUpdatedEvent $event
     * @return void
     */
    public function handle(BranchLocationUpdatedEvent $event): void
    {
        $branchId = $event->branchId;
        $latitude = $event->latitude;
        $longitude = $event->longitude;
        
        if (!$latitude || !$longitude) {
            return;
        }

        $constraints = AttendanceConstraint::query()
            ->whereJsonContains('branch_ids', (string) $branchId)
            ->where('constraint_config->default_location', true)
            ->get();

        foreach ($constraints as $constraint) {
            $branchLocations = $constraint->branch_locations;

            foreach ($branchLocations as &$location) {
                if ((string) $location['branch_id'] === (string) $branchId) {
                    $location['latitude'] = $latitude;
                    $location['longitude'] = $longitude;
                }
            }

            $constraint->branch_locations = $branchLocations;
            $constraint->save();
        }
    }
}

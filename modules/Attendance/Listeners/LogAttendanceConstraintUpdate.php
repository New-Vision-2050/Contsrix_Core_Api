<?php

namespace Modules\Attendance\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Events\AttendanceConstraintUpdated;

class LogAttendanceConstraintUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  AttendanceConstraintUpdated  $event
     * @return void
     */
    public function handle(AttendanceConstraintUpdated $event): void
    {
        $updatedConstraintId = $event->constraintId;

        $constraint = AttendanceConstraint::where('id', $updatedConstraintId)->first();

        if (!$constraint) {
            Log::warning('AttendanceConstraint with ID does not exist; cannot update related branches.', [
                'constraint_id' => $updatedConstraintId,
            ]);
            return;
        }

        $branchLocations = $constraint->branch_locations;

        if (!is_array($branchLocations) || empty($branchLocations)) {
            Log::info("Attendance constraint {$updatedConstraintId} has no branch locations defined; skipping branch coordinate check.");
            return;
        }

        // Collect all unique branch_ids referenced in the attendance constraint's branch_locations
        $referencedBranchIds = [];
        foreach ($branchLocations as $location) {
            if (isset($location['branch_id']) && !empty($location['branch_id'])) {
                $referencedBranchIds[] = $location['branch_id'];
            }
        }

        if (empty($referencedBranchIds)) {
            dd("No valid branch IDs found in attendance constraint {$updatedConstraintId} branch_locations.");
            Log::info("No valid branch IDs found in attendance constraint {$updatedConstraintId} branch_locations.");
            return;
        }

        // Fetch all relevant ManagementHierarchy (branch) models in one go
        $branches = ManagementHierarchy::whereIn('id', array_unique($referencedBranchIds))
                                        ->get()
                                        ->keyBy('id'); // Key by ID for easy lookup

        $branchesUpdatedCount = 0;

        // Iterate through each branch location defined in the attendance constraint
        foreach ($branchLocations as $locationDataFromConstraint) {

            $branchId = $locationDataFromConstraint['branch_id'] ?? null;
            $sourceLat = (float)($locationDataFromConstraint['latitude'] ?? 0.0);
            $sourceLong = (float)($locationDataFromConstraint['longitude'] ?? 0.0);

            // Ensure we have a branch ID from the constraint and valid source coordinates (not 0,0 from constraint)
            if ($branchId && ($sourceLat !== 0.0 || $sourceLong !== 0.0)) {

                // Get the actual ManagementHierarchy model for this branchId
                $actualBranch = $branches[$branchId] ?? null;

                if ($actualBranch) {
                    // Check if the actual branch's coordinates are null or 0
                    $currentBranchLat = (float)($actualBranch->latitude ?? 0.0);
                    $currentBranchLong = (float)($actualBranch->longitude ?? 0.0);

                    if (($actualBranch->latitude === null || $currentBranchLat === 0.0) &&
                        ($actualBranch->longitude === null || $currentBranchLong === 0.0)) {
                            // Only update if the branch's coordinates are currently null or (0,0)
                            $actualBranch->latitude = $sourceLat;
                            $actualBranch->longitude = $sourceLong;
                            $actualBranch->save(); // <--- IMPORTANT: Save the ManagementHierarchy model

                        $branchesUpdatedCount++;
                        Log::info("Updated ManagementHierarchy branch ID {$branchId} with coordinates from AttendanceConstraint.", [
                            'constraint_id' => $updatedConstraintId,
                            'branch_id' => $branchId,
                            'new_latitude' => $sourceLat,
                            'new_longitude' => $sourceLong,
                        ]);
                    } else {
                        Log::info("ManagementHierarchy branch ID {$branchId} already has valid non-zero coordinates; no update needed from constraint.", [
                            'constraint_id' => $updatedConstraintId,
                            'branch_id' => $branchId,
                            'current_latitude' => $actualBranch->latitude,
                            'current_longitude' => $actualBranch->longitude,
                        ]);
                    }
                } else {
                    Log::warning("Referenced ManagementHierarchy branch ID {$branchId} not found in database for constraint {$updatedConstraintId}.", [
                        'constraint_id' => $updatedConstraintId,
                        'branch_id' => $branchId
                    ]);
                }
            } else {
                Log::debug("Skipping branch location entry from constraint {$updatedConstraintId} as it lacks branch_id or valid non-zero source coordinates.", [
                    'entry' => $locationDataFromConstraint
                ]);
            }
        }

        if ($branchesUpdatedCount > 0) {
            Log::info("Completed processing constraint {$updatedConstraintId}. Successfully updated {$branchesUpdatedCount} ManagementHierarchy branch(es).");
        } else {
            Log::info("Completed processing constraint {$updatedConstraintId}. No ManagementHierarchy branches required coordinate updates.");
        }
    }
}

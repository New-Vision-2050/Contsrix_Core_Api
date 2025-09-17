<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Observers;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class UserCountObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param \Modules\User\Models\User $user
     * @return void
     */
    public function created(User $user): void
    {
        if ($user->management_hierarchy_id) {
            $this->recalculateUsersCountForHierarchy($user->management_hierarchy_id);
        }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param \Modules\User\Models\User $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Get the original management_hierarchy_id before the update
        $originalHierarchyId = $user->getOriginal('management_hierarchy_id');
        $newHierarchyId = $user->management_hierarchy_id;

        // If management_hierarchy_id changed, update both old and new hierarchies
        if ($originalHierarchyId != $newHierarchyId) {
            // Recalculate for the old hierarchy (if it exists)
            if ($originalHierarchyId) {
                $this->recalculateUsersCountForHierarchy($originalHierarchyId);
            }

            // Recalculate for the new hierarchy (if it exists)
            if ($newHierarchyId) {
                $this->recalculateUsersCountForHierarchy($newHierarchyId);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param \Modules\User\Models\User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        if ($user->management_hierarchy_id) {
            $this->recalculateUsersCountForHierarchy($user->management_hierarchy_id);
        }
    }

    /**
     * Handle the User "restored" event.
     *
     * @param \Modules\User\Models\User $user
     * @return void
     */
    public function restored(User $user): void
    {
        if ($user->management_hierarchy_id) {
            $this->recalculateUsersCountForHierarchy($user->management_hierarchy_id);
        }
    }

    /**
     * Recalculate users_count for a hierarchy and all its ancestors
     * This handles the recursive nature of the count
     */
    private function recalculateUsersCountForHierarchy($hierarchyId): void
    {
        $hierarchyId = (int)$hierarchyId; // Convert to int
        $hierarchy = ManagementHierarchy::find($hierarchyId);

        if (!$hierarchy) {
            return;
        }

        // Get all hierarchies that need to be updated (this hierarchy and all its ancestors)
        // Because when a user is added/removed from a child hierarchy,
        // all parent hierarchies need their counts updated too
        $hierarchiesToUpdate = ManagementHierarchy::query()
            ->whereSelfOrAncestorOf($hierarchy)
            ->pluck('id')
            ->toArray();

        foreach ($hierarchiesToUpdate as $hierarchyIdToUpdate) {
            $hierarchyToUpdate = ManagementHierarchy::find($hierarchyIdToUpdate);

            if ($hierarchyToUpdate) {
                // Get all descendant hierarchy IDs (including self) for this hierarchy
                $descendantIds = ManagementHierarchy::query()
                    ->whereSelfOrDescendantOf($hierarchyToUpdate)
                    ->pluck('id')
                    ->toArray();

                // Count all users in this hierarchy tree
                $actualCount = DB::table('users')
                    ->whereIn('management_hierarchy_id', $descendantIds)
                    ->whereNull('deleted_at')
                    ->count();

                // Update the count if it's different
                if ($hierarchyToUpdate->users_count != $actualCount) {
                    $hierarchyToUpdate->users_count = $actualCount;
                    $hierarchyToUpdate->saveQuietly(); // Use saveQuietly to avoid triggering observers
                }

                // Also update the corresponding record in managements or branches table
                if ($hierarchyToUpdate->type === 'branch') {
                    DB::table('branches')
                        ->where('management_hierarchy_id', $hierarchyToUpdate->id)
                        ->update(['users_count' => $actualCount]);
                } elseif ($hierarchyToUpdate->type === 'management') {
                    // For management hierarchies, handle copied vs non-copied
                    $detail = $hierarchyToUpdate->detail;

                    if ($detail && $detail->is_copied == 1) {
                        // If this is a copied hierarchy, update the source hierarchy in managements table
                        $sourceHierarchy = ManagementHierarchy::find($detail->reference_department_id);
                        if ($sourceHierarchy) {
                            // Calculate total users from all clones for the source hierarchy
                            $totalUsersFromClones = $sourceHierarchy->clones->sum(function ($clone) {
                                return $clone->managementHierarchy ? ($clone->managementHierarchy->users_count ?? 0) : 0;
                            });

                            // Update the source hierarchy's record in managements table
                            DB::table('managements')
                                ->where('management_hierarchy_id', $sourceHierarchy->id)
                                ->update(['users_count' => $totalUsersFromClones]);
                        }
                    } else {
                        // If this is a non-copied hierarchy, update its own record in managements table
                        DB::table('managements')
                            ->where('management_hierarchy_id', $hierarchyToUpdate->id)
                            ->update(['users_count' => $actualCount]);
                    }
                }
            }
        }
    }
}

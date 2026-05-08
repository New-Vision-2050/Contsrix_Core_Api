<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Observers;

use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\Branch;
use Modules\Company\ManagementHierarchy\Models\Management;
use Illuminate\Support\Facades\Schema;
use Modules\Attendance\Services\DefaultConstraintService;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\WorkFlow;

class ManagementHierarchyObserver
{
    /**
     * Inject the service via the constructor.
     * Laravel will automatically resolve it from the service container.
     */
    public function __construct(
        private DefaultConstraintService $defaultConstraintService
    ) {}
    /**
     * Handle the ManagementHierarchy "created" event.
     *
     * @param  \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy  $managementHierarchy
     * @return void
     */
    public function created(ManagementHierarchy $managementHierarchy): void
    {
        $this->syncRelatedTable($managementHierarchy);
        if ($managementHierarchy->type === 'branch') {
            $this->defaultConstraintService->createForBranch($managementHierarchy);
            $this->attachBranchToDefaultWorkFlow($managementHierarchy);
        }
    }

    /**
     * Handle the ManagementHierarchy "updated" event.
     *
     * @param  \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy  $managementHierarchy
     * @return void
     */
    public function updated(ManagementHierarchy $managementHierarchy): void
    {
        // If the type has changed, we need to remove from the old table and add to the new one.
        if ($managementHierarchy->isDirty('type')) {
            $originalType = $managementHierarchy->getOriginal('type');
            $this->deleteFromRelatedTable($managementHierarchy, $originalType);
            if ($originalType === 'branch' && $managementHierarchy->type !== 'branch') {
                $this->detachBranchFromWorkFlows($managementHierarchy);
            }
        }
        $this->syncRelatedTable($managementHierarchy);

        if ($managementHierarchy->type === 'branch') {
            $this->attachBranchToDefaultWorkFlow($managementHierarchy);
        }
    }

    /**
     * Handle the ManagementHierarchy "deleted" event.
     *
     * @param  \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy  $managementHierarchy
     * @return void
     */
    public function deleted(ManagementHierarchy $managementHierarchy): void
    {
        if ($managementHierarchy->type === 'branch') {
            $this->detachBranchFromWorkFlows($managementHierarchy);
        }
        $this->deleteFromRelatedTable($managementHierarchy, $managementHierarchy->type);
    }

    /**
     * Synchronize data with the appropriate related table (branches or managements).
     *
     * @param ManagementHierarchy $managementHierarchy
     * @return void
     */
    protected function syncRelatedTable(ManagementHierarchy $managementHierarchy): void
    {
        $data = $this->prepareData($managementHierarchy);

        if ($managementHierarchy->type === 'branch') {
            Branch::updateOrCreate(['management_hierarchy_id' => $managementHierarchy->id], $data);
        } elseif ($managementHierarchy->type === 'management') {
            // Check if this is a copied or non-copied hierarchy
            $isNonCopied = $managementHierarchy->detail && $managementHierarchy->detail->is_copied == 0;

            if ($isNonCopied) {
                // For non-copied hierarchies: create/update in managements table
                unset($data['is_first_branch']);
                Management::updateOrCreate(['management_hierarchy_id' => $managementHierarchy->id], $data);
            } else {
                // For copied hierarchies: only update users_count in main table
                $managementHierarchy->updateQuietly(['users_count' => $data['users_count']]);
            }
        }
    }

    /**
     * Delete data from the appropriate related table.
     *
     * @param ManagementHierarchy $managementHierarchy
     * @param string|null $type The type of the record before potential change (used in update)
     * @return void
     */
    protected function deleteFromRelatedTable(ManagementHierarchy $managementHierarchy, ?string $type): void
    {
        if ($type === 'branch') {
            Branch::where('management_hierarchy_id', $managementHierarchy->id)->delete();
        } elseif ($type === 'management') {
            // Only delete management records that were created (non-copied ones)
            // Check if this management hierarchy had a non-copied detail when it was created
            $hasNonCopiedRecord = Management::where('management_hierarchy_id', $managementHierarchy->id)->exists();

            if ($hasNonCopiedRecord) {
                Management::where('management_hierarchy_id', $managementHierarchy->id)->delete();
            }
        }
    }

    /**
     * Prepare data for insertion/update into related tables.
     *
     * @param ManagementHierarchy $managementHierarchy
     * @return array
     */
    protected function prepareData(ManagementHierarchy $managementHierarchy): array
    {
        // Calculate users_count from clones sum for management types
        $usersCount = $managementHierarchy->users_count ?? 0;
        if ($managementHierarchy->type === 'management') {
            $usersCount = $managementHierarchy->clones->sum(function ($clone) {
                return $clone->managementHierarchy ? ($clone->managementHierarchy->users_count ?? 0) : 0;
            });
        }

        // Ensure a UUID is generated if the related record is new
        // For updateOrCreate, if the record exists, its ID will be retained.
        // If it's new, we need a new UUID for the Branch/Management record itself.
        return [
            "id"=> $managementHierarchy->id,
            'management_hierarchy_id' => $managementHierarchy->id, // This is the FK to the main table
            'name' => $managementHierarchy->name,
            'parent_id' => $managementHierarchy->parent_id,
            'company_id' => $managementHierarchy->company_id,
            'path' => $managementHierarchy->path,
            'manager_id' => $managementHierarchy->manager_id,
            'phone' => $managementHierarchy->phone,
            'phone_code' => $managementHierarchy->phone_code,
            'email' => $managementHierarchy->email,
            'latitude' => $managementHierarchy->latitude,
            'longitude' => $managementHierarchy->longitude,
            'is_first_branch' => $managementHierarchy->is_first_branch??0, // Specific to branches
            'is_main' => $managementHierarchy->is_main??0,
            'users_count' => $usersCount,
        ];
    }

    protected function attachBranchToDefaultWorkFlow(ManagementHierarchy $managementHierarchy): void
    {
        if ($managementHierarchy->company_id === null) {
            return;
        }

        if (! Schema::hasTable('work_flows') || ! Schema::hasTable('management_hierarchy_work_flow')) {
            return;
        }

        $workFlowIds = [];
        foreach (ProcedureSettingType::cases() as $type) {
            $workFlowIds[] = WorkFlow::defaultForCompany(
                (string) $managementHierarchy->company_id,
                $type->value
            )->id;
        }

        $managementHierarchy->workFlows()->syncWithoutDetaching($workFlowIds);
    }

    protected function detachBranchFromWorkFlows(ManagementHierarchy $managementHierarchy): void
    {
        if (! Schema::hasTable('management_hierarchy_work_flow')) {
            return;
        }

        $managementHierarchy->workFlows()->detach();
    }
}

<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Services;

use Illuminate\Support\Facades\DB;
use Modules\Company\ManagementHierarchy\DTO\CloneDepartmentDTO;
use Modules\Company\ManagementHierarchy\DTO\CloneManagementDTO;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetail;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetailManager;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;
use Ramsey\Uuid\Uuid;

class ManagementHierarchyCloneService
{
    public function __construct(
        private ManagementHierarchyRepository $repository,
    ) {
    }


    public function cloneManagement(CloneManagementDTO $dto)
    {
        $sourceManagementHierarchy = $this->repository->getSourceManagementHierarchy($dto->sourceId);
        $detailTarget= $this->repository->getDetail($dto->taregtId);
        return $this->repository->createManagement($dto->managementToArray()+["name"=>$sourceManagementHierarchy->name,"type"=>$sourceManagementHierarchy->type],$dto->managementDetailToArray()+["branch_id"=>$detailTarget->branch_id],$dto->getDeputyManagerIds());

    }

    /**
     * Clone a department from one branch to another
     *
     * @param CloneDepartmentDTO $dto Data transfer object containing cloning parameters
     * @return ManagementHierarchy The newly created department
     */
    public function cloneDepartmentToBranch(CloneDepartmentDTO $dto): ManagementHierarchy
    {
        try {
            DB::beginTransaction();

            // Get source department
            $sourceDepartment = $this->repository->findOneByOrFail([
                'id' => $dto->sourceDepartmentId,
                'type' => 'management'
            ]);

            // Load relationships
            $sourceDepartment->load(['details', 'details.deputyManagerRelations']);

            // Determine the parent ID and branch ID for the cloned department
            $parentId = null;
            $branchId = $dto->targetBranchId;

            if ($dto->targetParentId) {
                // If a specific parent ID is provided, use it
                $targetParent = $this->repository->findOneBy([
                    'id' => $dto->targetParentId,
                    'type'=>'management'
                ]);

                if (!$targetParent) {
                    throw new \Exception("Target parent department or management not found");
                }

                // Get the branch ID from the parent if not explicitly provided
                if ($branchId === null) {
                    $parentDetail = $this->repository->getDetail($dto->targetParentId);

                    if (!$parentDetail || !$parentDetail->branch_id) {
                        throw new \Exception("Cannot determine branch ID from target parent");
                    }

                    $branchId = $parentDetail->branch_id;
                }

                $parentId = $dto->targetParentId;
            } else {
                $targetManagement = $this->repository->findOneBy([
                    'parent_id' => $branchId,
                    'type' => 'management',
                    'is_main' => 1
                ]);

                if (!$targetManagement) {
                    throw new \Exception("Target branch doesn't have a main management department");
                }

                $parentId = $targetManagement->id;
            }

            // Clone the department
            $clonedDepartment = $this->cloneDepartment($sourceDepartment, $parentId, $branchId, $dto->cloneManagers, $dto->overrideParams);

            // Link the departments
            $this->linkDepartments($sourceDepartment->id, $clonedDepartment->id);

            // Clone sub-departments if requested
            if ($dto->cloneSubDepartments) {
                $this->cloneSubDepartments($sourceDepartment->id, $clonedDepartment->id, $branchId, $dto->cloneManagers);
            }

            DB::commit();
            return $clonedDepartment;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Clone a single department
     *
     * @param ManagementHierarchy $sourceDepartment The department to clone
     * @param string|int $parentId The ID of the parent (management or department)
     * @param string|int $branchId The ID of the branch
     * @param bool $cloneManagers Whether to clone manager assignments
     * @param array $overrides Optional override parameters for the cloned department
     * @return ManagementHierarchy The newly created department
     */
    private function cloneDepartment(ManagementHierarchy $sourceDepartment, $parentId, $branchId, bool $cloneManagers = true, array $overrides = []): ManagementHierarchy
    {
        // Create department data array
        $departmentData = [
            'name' => $sourceDepartment->name,
            'parent_id' => $parentId,
            'company_id' => $sourceDepartment->company_id,
            'type' => 'management',
            'is_main' => 0, // Never set as main when cloning
            'is_active' => $sourceDepartment->is_active,
            'manager_id' => $sourceDepartment->manager_id, // Clone the manager assignment
        ];

        // Apply override parameters
        $departmentData = array_merge($departmentData, $overrides);

        // Create department detail data array
        $departmentDetail = [];

        if ($sourceDepartment->detail) {
            $departmentDetail = [
                'description' => $sourceDepartment->detail->description,
                'reference_user_id' => $sourceDepartment->detail->reference_user_id,
                'branch_id' => $branchId,
                'reference_department_id' => $sourceDepartment->id,
                "is_copied" => 1
            ];
        }

        // Create the department using repository
        $newDepartment = $this->repository->createDepartment($departmentData, $departmentDetail,);

        // Clone deputy managers if requested
        if ($cloneManagers && $sourceDepartment->detail && $sourceDepartment->detail->deputyManagerRelations) {
            foreach ($sourceDepartment->detail->deputyManagerRelations as $deputyManager) {
                $newDeputyManager = new ManagementHierarchyDetailManager();
                $newDeputyManager->id = Uuid::uuid4();
                $newDeputyManager->deputy_manager_id = $deputyManager->deputy_manager_id;
                $newDeputyManager->management_hierarchy_detail_id = $newDepartment->detail->id;
                $newDeputyManager->save();
            }
        }

        return $newDepartment;
    }

    /**
     * Link source and target departments to track relationships
     *
     * @param string|int $sourceDepartmentId The ID of the source department
     * @param string|int $targetDepartmentId The ID of the target department
     */
    private function linkDepartments($sourceDepartmentId, $targetDepartmentId): void
    {
        // This is now handled in the cloneDepartment method using reference_department_id
        // This method is kept for potential future enhancements to department linking
        $targetDetail = $this->repository->getDetail($targetDepartmentId);
        if ($targetDetail && !$targetDetail->reference_department_id) {
            $targetDetail->reference_department_id = $sourceDepartmentId;
            $targetDetail->save();
        }
    }

    /**
     * Clone all sub-departments recursively
     *
     * @param string|int $sourceDepartmentId The ID of the source department
     * @param string|int $targetDepartmentId The ID of the target department
     * @param string|int $branchId The ID of the branch
     * @param bool $cloneManagers Whether to clone manager assignments
     */
    private function cloneSubDepartments($sourceDepartmentId, $targetDepartmentId, $branchId, bool $cloneManagers = true): void
    {
        // Get all sub-departments
        $subDepartments = $this->repository->model
            ->where('parent_id', $sourceDepartmentId)
            ->where('type', 'department')
            ->with(['detail', 'detail.deputyManagerRelations'])
            ->get();

        foreach ($subDepartments as $subDepartment) {
            // Clone the sub-department
            $clonedSubDepartment = $this->cloneDepartment($subDepartment, $targetDepartmentId, $branchId, $cloneManagers);

            // Link the departments
            $this->linkDepartments($subDepartment->id, $clonedSubDepartment->id);

            // Recursively clone sub-departments
            $this->cloneSubDepartments($subDepartment->id, $clonedSubDepartment->id, $branchId, $cloneManagers);
        }
    }

    /**
     * Get all departments that have been cloned from a source department
     *
     * @param string|int $departmentId The ID of the source department
     * @return \Illuminate\Database\Eloquent\Collection Collection of linked departments
     */
    public function getLinkedDepartments($departmentId)
    {
        return $this->repository->getLinkedDepartmentsByReference($departmentId);
    }

    /**
     * Sync changes between linked departments
     *
     * @param string|int $sourceDepartmentId The ID of the source department
     * @param array $fieldsToSync Array of fields to sync (e.g., ['name', 'description'])
     * @param bool $syncManagers Whether to sync manager assignments
     */
    public function syncLinkedDepartments($sourceDepartmentId, array $fieldsToSync = ['name'], bool $syncManagers = false): void
    {
        try {
            DB::beginTransaction();

            // Get source department
            $sourceDepartment = $this->repository->findOneByOrFail(['id' => $sourceDepartmentId]);
            $sourceDepartment->load(['detail']);

            // Get linked departments
            $linkedDepartments = $this->getLinkedDepartments($sourceDepartmentId);

            foreach ($linkedDepartments as $linkedDepartment) {
                $updateData = [];

                // Sync department fields
                foreach ($fieldsToSync as $field) {
                    if (in_array($field, $sourceDepartment->getFillable()) && isset($sourceDepartment->$field)) {
                        $updateData[$field] = $sourceDepartment->$field;
                    }
                }

                if (!empty($updateData)) {
                    $linkedDepartment->update($updateData);
                }

                // Sync detail fields if needed
                if ($sourceDepartment->detail && $linkedDepartment->detail) {
                    $detailUpdateData = [];

                    foreach ($fieldsToSync as $field) {
                        if (in_array($field, $sourceDepartment->detail->getFillable()) && isset($sourceDepartment->detail->$field)) {
                            $detailUpdateData[$field] = $sourceDepartment->detail->$field;
                        }
                    }

                    if (!empty($detailUpdateData)) {
                        $linkedDepartment->detail->update($detailUpdateData);
                    }
                }

                // Sync managers if requested
                if ($syncManagers) {
                    // Sync main manager
                    $linkedDepartment->manager_id = $sourceDepartment->manager_id;
                    $linkedDepartment->save();

                    // Sync deputy managers
                    if ($sourceDepartment->detail && $linkedDepartment->detail) {
                        // Remove existing deputy managers
                        $this->repository->deleteDeputyManagers($linkedDepartment->detail->id);

                        // Add new deputy managers
                        $deputyManagers = $this->repository->getDeputyManagers($sourceDepartment->detail->id);

                        foreach ($deputyManagers as $deputyManager) {
                            $newDeputyManager = new ManagementHierarchyDetailManager();
                            $newDeputyManager->id = Uuid::uuid4();
                            $newDeputyManager->deputy_manager_id = $deputyManager->deputy_manager_id;
                            $newDeputyManager->management_hierarchy_detail_id = $linkedDepartment->detail->id;
                            $newDeputyManager->save();
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

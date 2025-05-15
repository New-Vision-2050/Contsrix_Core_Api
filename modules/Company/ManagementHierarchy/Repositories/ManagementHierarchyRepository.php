<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Repositories;

use App\Scopes\CustomTenantScope;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchyDetailManager;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;

/**
 * @property ManagementHierarchy $model
 * @method ManagementHierarchy findOneOrFail($id)
 * @method ManagementHierarchy findOneByOrFail(array $data)
 */
class ManagementHierarchyRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public $nextId;

    public function __construct(ManagementHierarchy $model)
    {
        parent::__construct($model);
        $this->nextId = $model->query()->orderBy("id", "desc")->withoutGlobalScope(CustomTenantScope::class)->first()->id + 1;
    }

    public function getManagementHierarchyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAll()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $managementHierarchy = null;
        if (request()->has("parent_children_id")) {
            $managementHierarchy = $this->model->where("id", request()->parent_children_id)->where("company_id", $company->id)->first();

        }

        return $this->model->filter(request()->all())
            ->when(request()->has("parent_children_id") && $managementHierarchy, function ($query) use ($managementHierarchy) {
                $query->whereSelfOrDescendantOf($managementHierarchy);

            })->where("company_id", $company->id)->get();
    }

    public function getTree()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $managementHierarchy = null;
        if (request()->has("id")) {
            $managementHierarchy = $this->model->where("id", request()->id)->where("company_id", $company->id)->first();

        }

        return $this->model->where("company_id", $company->id)->with(["user.companyUser.media", "users", "directUserChildren", "detail"])
            ->when(request()->has("type"), function ($query) {
                if (request()->type == "management") {
                    $query->where("type", "management");
                }

            })
            ->when(request()->has("id") && $managementHierarchy, function ($query) use ($managementHierarchy) {
                $query->whereSelfOrDescendantOf($managementHierarchy);

            })->get()->tree();
    }

    public function getManagementHierarchy(int $id): ManagementHierarchy
    {
        return $this->findOneByOrFail([
            'id' => $id,
        ]);
    }

    public function getMainBranchForCompany($id): ManagementHierarchy
    {
        return $this->findOneBy([
            "company_id" => $id,
            "parent_id" => null,
            "type" => "branch"
        ]);
    }

    public function createBranch(array $branchData, array $addressData): ManagementHierarchy
    {
        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($branchData + ["id" => $this->nextId]);

            $this->nextId = $this->nextId+1;
            $this->createManagement(["company_id" => $managementHierarchy->company_id,"parent_id"=>$managementHierarchy->id, "is_main"=>1,"name" => "الادارة العامة", "type" => "management"], ["description"=>"الادارة العامة","branch_id"=>$managementHierarchy->id],[]);

            $managementHierarchy->address()->create($addressData + ["company_id" => $managementHierarchy->company_id]);


            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return $managementHierarchy;
    }

    public function createManagementHierarchy(array $managementHierarchyData): ManagementHierarchy
    {

        $managementHierarchy = $this->create($managementHierarchyData + ["id" => $this->nextId]);
        return $managementHierarchy;
    }

    public function createManagement(array $managementData, array $managementDetail, array $deputyManagers): ManagementHierarchy
    {

        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($managementData + ["id" => $this->nextId]);
            $detail = $managementHierarchy->detail()->create($managementDetail);
            if (count($deputyManagers) > 0) {
                foreach ($deputyManagers as $deputyManager) {
                    ManagementHierarchyDetailManager::create(["deputy_manager_id" => $deputyManager, "management_hierarchy_detail_id" => $managementHierarchy->detail->id]);

                }

            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return $managementHierarchy;
    }


    public function createDepartment(array $departmentData, array $departmentDetail): ManagementHierarchy
    {

        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($departmentData + ["id" => $this->nextId, "manager_id" => User::query()->where("is_owner", 1)->first()?->id]);
            $managementHierarchy->detail()->create($departmentDetail);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return $managementHierarchy;
    }

    public function updateManagementHierarchy(int $id, array $branchData, array $addressData): bool
    {
        try {
            DB::beginTransaction();
            $managementHierarchy = $this->find($id);
            $managementHierarchy->update($branchData);
            $managementHierarchy->fresh();

            $managementHierarchy->address()->update($addressData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);

        }
        return true;
    }

    public function updateManagement(int $id, array $managementData, array $managementDetail, array $deputyManagers): bool
    {
        try {
            DB::beginTransaction();

            // Update management hierarchy
            $managementHierarchy = $this->findOneOrFail($id);
            $managementHierarchy->update($managementData);

            // Update management detail
            if ($managementHierarchy->detail) {
                $managementHierarchy->detail->update($managementDetail);
            } else {
                $managementHierarchy->detail()->create($managementDetail);
            }

            // Delete existing deputy managers and create new ones
            if ($managementHierarchy->detail) {
                $detailId = $managementHierarchy->detail->id;
                ManagementHierarchyDetailManager::where('management_hierarchy_detail_id', $detailId)->delete();

                // Create new deputy managers
                if (count($deputyManagers) > 0) {
                    foreach ($deputyManagers as $deputyManager) {
                        ManagementHierarchyDetailManager::create([
                            'deputy_manager_id' => $deputyManager,
                            'management_hierarchy_detail_id' => $detailId
                        ]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage(), 500);
        }
    }

    /**
     * Check if a management hierarchy has any children
     *
     * @param int $id The ID of the management hierarchy to check
     * @return bool True if it has children, false otherwise
     */
    public function hasChildren(int $id): bool
    {
        $managementHierarchy = $this->findOneOrFail($id);

        // Check for direct management hierarchy children
        $childrenCount = $this->model->where('parent_id', $id)->count();
        if ($childrenCount > 0) {
            return true;
        }

        // Check for user children/employees
        $userChildrenCount = $managementHierarchy->directUserChildren()->count();
        return $userChildrenCount > 0;
    }

    public function deleteManagementHierarchy(int $id): bool
    {
        return $this->delete($id);
    }

    public function makeMainBranch(int $id, int $branchId)
    {
        $mainBranch = $this->find($id);
        $otherMainBranchesCount = $this->model->where('id', "<>", $id)->where("company_id", $mainBranch->company_id)->where("type", "branch")->whereNull("parent_id")->count();


        $mainBranch->update(["is_main" => false]);

        $newMainBranch = $this->find($branchId);
        $newMainBranch->update(["is_main" => true]);

        if ($otherMainBranchesCount) {
            // If there are other main branches, simply update the parent_id of the old main branch
            $mainBranch->update(["parent_id" => $branchId]);
        } else {
            try {
                DB::beginTransaction();
                // If this is a swap operation
                $newMainBranch = $this->find($branchId);

                // Store the original parent of the new main branch
                $originalParentId = $newMainBranch->parent_id;

                // First, detach the new main branch from its parent
                $newMainBranch->parent_id = null;
                $newMainBranch->save();


                // Then update the old main branch's parent to be the original parent of the new main branch
                $mainBranch->update(["parent_id" => $branchId]);
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();

                throw new \Exception($e->getMessage(), 500);
            }


        }
    }

    /**
     * Get count statistics for hierarchies by type
     *
     * @param string $type The hierarchy type (branch, management, department)
     * @param mixed $companyId The company ID
     * @return array
     */
    public function getHierarchyCountStatistics(string $type, $companyId): array
    {
        // Total count of the hierarchy type
        $totalCount = $this->model
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->count();

        // Count of hierarchy items used in user records
        $usedCount = $this->model
            ->where('company_id', $companyId)
            ->where('type', $type)
            ->whereHas('directUserChildren')
            ->count();

        // Count of hierarchy items not used in user records
        $unusedCount = $totalCount - $usedCount;

        return [
            'total_count' => $totalCount,
            'used_count' => $usedCount,
            'unused_count' => $unusedCount
        ];
    }

    /**
     * Get all lower level users in the management hierarchy tree for a specific user
     * First checks if the user is a manager or deputy manager of any hierarchy
     * get user in all hirarchy recuservly and make it unique to omit redduncey
     *
     * @param int $userId The ID of the user
     */
    public function getUserLowerLevels(UuidInterface $userId)
    {
        // Get the user
        $user = User::findOrFail($userId);
        $lowerUsers = collect();
        $managementHierarchies = collect();
        $lowerUsers->push($user);// Add the user as an option

        // Check if the user is a manager of any hierarchy
        $managerHierarchies = $this->model->where('manager_id', $userId)->get();

        // Check if the user is a deputy manager in any hierarchy
        $deputyManagerDetails = ManagementHierarchyDetailManager::where('deputy_manager_id', $userId)
            ->with('managementHierarchyDetail.managementHierarchy')
            ->get();

        $deputyManagerHierarchies = collect();
        foreach ($deputyManagerDetails as $deputyDetail) {
            if ($deputyDetail->managementHierarchyDetail && $deputyDetail->managementHierarchyDetail->managementHierarchy) {
                $deputyManagerHierarchies->push($deputyDetail->managementHierarchyDetail->managementHierarchy);
            }
        }

        // Combine all hierarchies where the user is in a management position
        $managementHierarchies = $managerHierarchies->merge($deputyManagerHierarchies);

        // If user is not a manager or deputy manager anywhere, use their assigned hierarchy
        if ($user->management_hierarchy_id) {
            $userHierarchy = $this->model
                ->where('id', $user->management_hierarchy_id)
                ->first();

            if ($userHierarchy) {
                $managementHierarchies->push($userHierarchy);
            }
        }

        // For each hierarchy where the user has a management role, get all descendants
        foreach ($managementHierarchies as $hierarchy) {
            // Get all descendants of this hierarchy
            $descendants = $hierarchy->descendants()->with(['user', 'detail.deputyManagers', 'directUserChildren'])->get();

            // Collect all users from descendants
            foreach ($descendants as $descendant) {
                // Add the main manager if exists and it's not the current user
                if ($descendant->user && $descendant->user->id !== $userId) {
                    $lowerUsers->push($descendant->user);
                }

                // Add deputy managers if they exist and not the current user
                if ($descendant->detail && $descendant->detail->deputyManagers) {
                    foreach ($descendant->detail->deputyManagers as $deputy) {
                        if ($deputy->id !== $userId) {
                            $lowerUsers->push($deputy);
                        }
                    }
                }

                // Add direct user children assigned to this management hierarchy
                if ($descendant->directUserChildren) {
                    foreach ($descendant->directUserChildren as $directUser) {
                        if ($directUser->id !== $userId) {
                            $lowerUsers->push($directUser);
                        }
                    }
                }
            }

            // Also add direct user children from the current hierarchy (if not the original user)
            if ($hierarchy->directUserChildren) {
                foreach ($hierarchy->directUserChildren as $directUser) {
                    if ($directUser->id !== $userId) {
                        $lowerUsers->push($directUser);
                    }
                }
            }
        }

        return $lowerUsers->unique('id');
    }
}

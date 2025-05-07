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

        return $this->model->filter(request()->all())->where("company_id", $company->id)->get();
    }

    public function getTree()
    {
        [$company, $branch] = $this->declareCompanyAndBranchUsingRequest();
        $managementHierarchy = null;
        if (request()->has("id")) {
            $managementHierarchy = $this->model->where("id", request()->id)->where("company_id", $company->id)->first();

        }

        return $this->model->where("company_id", $company->id)->with(["user.companyUser.media", "users", "directUserChildren","detail"])
            ->when(request()->has("type"), function ($query) {
                if (request()->type == "management") {
                    $query->where("type", "management")->orWhere("type", "department");
                } elseif (request()->type == "department") {

                    $query->where("type", "department");
                }
            })->when(request()->has("id")&& $managementHierarchy, function ($query) use ($managementHierarchy) {
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

    public function createManagement(array $managementData, array $managementDetail,array $deputyManagers): ManagementHierarchy
    {

        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($managementData + ["id" => $this->nextId, "manager_id" => User::query()->where("is_owner", 1)->first()?->id]);
            $detail =$managementHierarchy->detail()->create($managementDetail);
            if(count($deputyManagers)>0){
                foreach ($deputyManagers as $deputyManager){
                    ManagementHierarchyDetailManager::create( ["deputy_manager_id"=>$deputyManager, "management_hierarchy_detail_id" => $managementHierarchy->detail->id]);

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
}

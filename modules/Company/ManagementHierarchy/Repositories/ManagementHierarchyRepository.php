<?php

declare(strict_types=1);

namespace Modules\Company\ManagementHierarchy\Repositories;

use App\Scopes\CustomTenantScope;
use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Traits\PreDeclareComapnyAndBranchDependOnReqeuest;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

/**
 * @property ManagementHierarchy $model
 * @method ManagementHierarchy findOneOrFail($id)
 * @method ManagementHierarchy findOneByOrFail(array $data)
 */
class ManagementHierarchyRepository extends BaseRepository
{
    use PreDeclareComapnyAndBranchDependOnReqeuest;

    public function __construct(ManagementHierarchy $model)
    {
        parent::__construct($model);
        $this->nextId = $model->query()->orderBy("id","desc")->withoutGlobalScope(CustomTenantScope::class)->first()->id+1;
    }

    public function getManagementHierarchyList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAll()
    {
        [$company ,$branch]=$this->declareCompanyAndBranchUsingRequest();

        return $this->model->filter(request()->all())->where("company_id", $company->id)->get();
    }

    public function getManagementHierarchy(UuidInterface $id): ManagementHierarchy
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function getMainBranchForCompany(UuidInterface $id): ManagementHierarchy
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
            $managementHierarchy = $this->create($branchData+["id"=>$this->nextId]);

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

        $managementHierarchy = $this->create($managementHierarchyData+["id"=>$this->nextId] );
        return $managementHierarchy;
    }

    public function createManagement(array $managementData, array $managementDetail): ManagementHierarchy
    {

        try {
            DB::beginTransaction();
            $managementHierarchy = $this->create($managementData + ["id"=>$this->nextId, "manager_id" => User::query()->where("is_owner", 1)->first()?->id]);
            $managementHierarchy->detail()->create($managementDetail);
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

    public function updateManagementHierarchy(UuidInterface $id, array $branchData, array $addressData): bool
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

    public function deleteManagementHierarchy(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function makeMainBranch(UuidInterface $id, UuidInterface $branchId)
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
}

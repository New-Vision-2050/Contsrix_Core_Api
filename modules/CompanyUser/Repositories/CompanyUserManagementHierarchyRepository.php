<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\CompanyUser\Models\CompanyUserCompanyManagementHierarchy;

/**
 * @property CompanyUserCompanyManagementHierarchy $model
 * @method CompanyUserCompanyManagementHierarchy findOneOrFail($id)
 * @method CompanyUserCompanyManagementHierarchy findOneByOrFail(array $data)
 */
class CompanyUserManagementHierarchyRepository extends BaseRepository
{
    public function __construct(CompanyUserCompanyManagementHierarchy $model)
    {
        parent::__construct($model);
    }

    /**
     * Delete hierarchy associations by criteria
     */
    public function deleteWhere(array $conditions): bool
    {
        return (bool) $this->model->where($conditions)->delete();
    }



    /**
     * Get user branches by criteria
     */
    public function getUserInBranches(string $globalId, int $role, array $branchIds)
    {
        return $this->model->whereIn("management_hierarchy_id", $branchIds)
            ->whereHas("companyUserCompany", function ($query) use ($globalId, $role) {
                $query->where("global_company_user_id", $globalId)
                    ->where("role", $role)
                    ->where("company_id", tenant("id"));
            })
            ->get();
    }

    /**
     * Count records by criteria
     */
    public function countWhere(array $conditions): int
    {
        return $this->model->where($conditions)->count();
    }


}

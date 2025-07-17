<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Repositories;

use Illuminate\Support\Collection;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Subscription\Package\Models\CompanyPermissionLimit;

/**
 * @property CompanyPermissionLimit $model
 * @method CompanyPermissionLimit findOneOrFail($id)
 * @method CompanyPermissionLimit findOneByOrFail(array $data)
 */
class CompanyPermissionLimitRepository extends BaseRepository
{
    public function __construct(CompanyPermissionLimit $model)
    {
        parent::__construct($model);
    }

    /**
     * Delete all limits for a company.
     */
    public function deleteByCompanyId(string $companyId): void
    {
        $this->model->where('company_id', $companyId)->delete();
    }

    /**
     * Bulk insert permission limits.
     */
    public function bulkInsert(array $limitsData): void
    {
        if (!empty($limitsData)) {
            $this->model->insert($limitsData);
        }
    }

    /**
     * Find limit by company and permission.
     */
    public function findByCompanyAndPermission(string $companyId, string $permissionId): ?CompanyPermissionLimit
    {
        return $this->model->where('company_id', $companyId)
            ->where('permission_id', $permissionId)
            ->first();
    }

    /**
     * Get all limits for a company.
     */
    public function getByCompanyId(string $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }


}

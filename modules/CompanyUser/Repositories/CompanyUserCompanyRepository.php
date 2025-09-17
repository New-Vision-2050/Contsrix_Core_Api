<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\CompanyUser\Models\CompanyUserCompany;
use function PHPUnit\Framework\assertStringEqualsStringIgnoringLineEndings;

/**
 * @property CompanyUserCompany $model
 * @method CompanyUserCompany findOneOrFail($id)
 * @method CompanyUserCompany findOneByOrFail(array $data)
 */
class CompanyUserCompanyRepository extends BaseRepository
{
    public function __construct(CompanyUserCompany $model)
    {
        parent::__construct($model);
    }

    /**
     * Find company user role with trashed records
     */
    public function findWithTrashed(array $conditions): ?CompanyUserCompany
    {
        return $this->model->withTrashed()
            ->withoutTenancy()
            ->where($conditions)
            ->first();
    }

    /**
     * Create or restore company user role
     */
    public function createOrRestore(array $data): CompanyUserCompany
    {
        $companyUserCompany = $this->findWithTrashed($data);

        if (!$companyUserCompany) {
            return $this->create($data);
        } elseif ($companyUserCompany->deleted_at !== null) {
            $companyUserCompany->restore();
        }else{
             $companyUserCompany->update($data);
             $companyUserCompany->fresh();
        }

        return $companyUserCompany;
    }


    /**
     * Delete company user roles by criteria
     */
    public function deleteWhere(array $conditions): bool
    {
        return (bool) $this->model->where($conditions)->delete();
    }

    /**
     * Count records by criteria
     */
    public function countWhere(array $conditions): int
    {
        return $this->model->where($conditions)->count();
    }


}

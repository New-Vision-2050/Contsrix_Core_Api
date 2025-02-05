<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Composer\Autoload\ClassLoader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Ramsey\Uuid\UuidInterface;
use Modules\CompanyUser\Models\CompanyUser;

/**
 * @property CompanyUser $model
 * @method CompanyUser findOneOrFail($id)
 * @method CompanyUser findOneByOrFail(array $data)
 */
class CompanyUserRepository extends BaseRepository
{

    public function __construct(CompanyUser $model)
    {
        parent::__construct($model);
    }

    public function getCompanyUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getCompanyUser(UuidInterface $id): CompanyUser
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createCompanyUser(array $companyUserData,array $companyRole): CompanyUser
    {
        try {
            DB::beginTransaction();
            $companyUser= $this->create($companyUserData);
            CompanyUserCompany::create($companyRole+["company_user_id"=>$companyUser->id]);
            DB::commit();
        }catch (\Exception $exception){
            DB::rollBack();
            throw new \Exception(__("create-not-successful"),500);
        }

        return $companyUser;
    }


    public function assignRoleCompanyUser(UuidInterface $id , array $companyUserRoleData):void
    {
        CompanyUserCompany::create($companyUserRoleData+["company_user_id"=>$id]);
    }

    public function updateCompanyUser(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteCompanyUser(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

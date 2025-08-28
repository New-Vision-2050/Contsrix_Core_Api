<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\Audit\Repositories\AuditRepository;
use Modules\CompanyUser\Models\CompanyUserCompany;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\Setting\Repositories\IdentifierSettingRepository;

/**
 * @property User $model
 * @method User findOneOrFail($id)
 * @method User findOneByOrFail(array $data)
 */
class UserRepository extends BaseRepository
{
    public function __construct(
        User $model,
        private AuditRepository $auditRepository,
        private IdentifierSettingRepository $identifierSettingRepository
    ) {
        parent::__construct($model);
    }

    public function getUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUser(UuidInterface $id): User
    {
        return $this->model->withoutTenancy()->findOrFail(
             $id->toString()
        );
    }

    public function getUserByEmail($email): User
    {
        return $this->findOneByWithRelationsOrFail([
            'email' => $email,
        ], ["loginWay"]);
    }

    public function getUserByIdentifier($identifier): mixed
    {
        $identifierSettings = $this->identifierSettingRepository->list();
        $isEmailActive = $identifierSettings->where('key', 'email')->first()->status;
        $isPhoneActive = $identifierSettings->where('key', 'phone')->first()->status;
        return $this->model->query()->where(function ($query) use ($identifier, $isEmailActive, $isPhoneActive) {
            $query->when($isEmailActive == 1, function ($query) use ($identifier) {
                return $query->where('email', $identifier);
            })->when($isPhoneActive == 1, function ($query) use ($identifier) {
                return $query->orWhere('phone', $identifier);
            });
        })->where("company_id", tenant("id"))->first();
    }

    public function getUserByGlobalIdWithBranches($global_id,$role=1)
    {
        $user = $this->model->query()->where('global_company_user_id', $global_id)->where("company_id", tenant("id"))->first();
        return CompanyUserCompany::query()->where("company_id", tenant("id"))
            ->where("global_company_user_id", $user?->global_company_user_id)
            ->with("managementHierarchy")
            ->get();

    }

    public function getUserInCurrentCompanyWith(array $relations = [], $type = null, $page = 1, $perPage = 10)
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model;
        }
        $query = $query->with(array_merge(
            $relations,
            [
                "companyUserCompanies" => function ($query) {
                    $query->where("company_id", tenant("id"));
                }
            ]
        ));
        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("companyUserCompanies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type)->when(request()->has("status"),function ($query) {
                    $query->where("company_users_companies.status", request()->status);
                });
            });
        })->when(request()->has("branch_id"), function ($query) use ($type) {
            $query->whereHas('companyUserCompanyManagementHierarchies', function ($hierarchyQuery) use ( $type) {
                    $hierarchyQuery->where('management_hierarchy_id', request()->branch_id)

                });
        })

            ->where("company_id", tenant("id"));
        //TODO filter with branches very important

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, ['data' => $paginatedData]);
    }


    public function getUserInCurrentCompanyByRole($id , array $relations = [], $type = null )
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model;
        }
        $user = $query->with(array_merge(
            $relations,
            [
                "companyUserCompanies" => function ($query) {
                    $query->where("company_id", tenant("id"));
                }
            ]
        ))->when($type != null, function ($query) use ($type) {
            $query->whereHas("companyUserCompanies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type);
            });
        })->where("company_id", tenant("id"))->where("id", $id)->first();
        //TODO filter with branches very important

        return $user;
    }

    public function getBrokerInCurrentCompanyWith($page = 1, $perPage = 10)
    {
        $type = CompanyUserRole::BROKER->value;

        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model;
        }
        $query = $query->with(array_merge(
            [
                'companyUser:id,global_id,country_id,job_title_id',
                'companyUser.nationalAddress',
                'companyUser.nationalAddress.country:id,name,native',
                'companyUser.nationalAddress.state:id,name',
                'companyUser.nationalAddress.city:id,name',
            ]
        ));
        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("companyUserCompanies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type);
            });
        })->where("company_id", tenant("id"))
         ->select('id', 'name', 'email', 'phone', 'status', 'global_company_user_id', 'company_id');

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function getEmployeeInCurrentCompanyWith($page = 1, $perPage = 10)
    {
        $type = CompanyUserRole::EMPLOYEE->value;

        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model;
        }
        $query = $query->with(array_merge(
            [
                "companyUserCompanies" => function ($query) {
                    $query->where("company_id", tenant("id"));
                },
                'companyUser:id,global_id',
                'companyUser.jobTitle:id,type,status,company_id',
                'companyUser.country:id,name,native',
                'branch:id,name,type,is_active'
            ]
        ));
        $query = $query->when($type != null, function ($query) use ($type) {
            $query->whereHas("companyUserCompanies", function ($query) use ($type) {
                $query->where("company_users_companies.role", $type);
            });
        })->where("company_id", tenant("id"))
         ->select('id', 'name', 'email', 'phone', 'global_company_user_id', 'company_id', 'is_owner', 'management_hierarchy_id', 'status');

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function createUser(array $data): User
    {
        return $this->create($data);
    }

    public function updateUser(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUser(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function assignRole(UuidInterface $id, $roles): User
    {
        $user = $this->getUser($id);
        $user->syncRoles($roles);
        return $user;
    }

    public function getRoles(UuidInterface $id)
    {
        return $this->getUser($id)->roles;
    }

    public function getPermissions(UuidInterface $id)
    {
        return $this->getUser($id)->getAllPermissions();
    }

    public function getAllAudites(UuidInterface $id, ?int $page, ?int $perPage = 10)
    {
        return $this->auditRepository->paginated(["user_id" => $id, "user_type" => User::class], $page, $perPage);
    }

    public function deleteWhere(array $conditions)
    {
        $this->model->where($conditions)->delete();
    }

    public function getWithoutTenancy()
    {
        $this->model->withoutTenancy();
        return $this;
    }

    public function getWherePluck(array $conditions, $pluck): array
    {
        return $this->model->where($conditions)->pluck($pluck)->toArray();
    }

    public function getAdminUsersFromCentralCompanies($page, $perPage)
    {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all());
        } else {
            $query = $this->model;
        }
        $query = $query->distinct("global_company_user_id")->withoutTenancy()->whereNotNull("management_hierarchy_id")//mean this is employee not any type else
            ->whereHas('company', function ($query) {
                $query->where('is_central_company', true);
            });

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy('created_at', 'desc')->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function getUsersWithRelations(array $userIds = null, array $relations = []): Collection
    {
        $query = $this->model->with($relations);

        if ($userIds) {
            $query->whereIn('global_company_user_id', $userIds);
        }

        if (method_exists($this->model, 'scopeFilter')) {
            $query->filter(request()->all());
        }

        return $query->get();
    }

    /**
     * Get user count statistics for a company
     *
     * @param string|int $companyId
     * @return array
     */
    public function getUserCountStatistics($companyId): array
    {
        // Total users in the company
        $totalUsers = $this->model->where('company_id', $companyId)->count();

        // Users with hierarchy ID
        $usersWithHierarchy = $this->model->where('company_id', $companyId)
            ->whereNotNull('management_hierarchy_id')
            ->count();

        // Users without hierarchy ID
        $usersWithoutHierarchy = $this->model->where('company_id', $companyId)
            ->whereNull('management_hierarchy_id')
            ->count();

        return [
            'total_users' => $totalUsers,
            'users_with_hierarchy' => $usersWithHierarchy,
            'users_without_hierarchy' => $usersWithoutHierarchy
        ];
    }
}

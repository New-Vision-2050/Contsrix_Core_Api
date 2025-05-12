<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Audit\Repositories\AuditRepository;
use Modules\Setting\Repositories\IdentifierSettingRepository;
use Ramsey\Uuid\UuidInterface;
use Modules\User\Models\User;

/**
 * @property User $model
 * @method User findOneOrFail($id)
 * @method User findOneByOrFail(array $data)
 */
class UserRepository extends BaseRepository
{
    public function __construct(
        User                              $model,
        private AuditRepository             $auditRepository,
        private IdentifierSettingRepository $identifierSettingRepository)
    {
        parent::__construct($model);
    }

    public function getUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUser(UuidInterface $id): User
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
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
        $query = $this->model->withoutTenancy()
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

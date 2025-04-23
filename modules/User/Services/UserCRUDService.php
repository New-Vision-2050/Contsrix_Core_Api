<?php

declare(strict_types=1);

namespace Modules\User\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\RoleAndPermission\Models\Permission;
use Modules\User\DTO\CreateUserDTO;
use Modules\User\Models\User;
use Modules\User\Repositories\UserRepository;
use Ramsey\Uuid\UuidInterface;

class UserCRUDService
{
    public function __construct(
        private UserRepository $repository,
        private CompanyRepository $companyRepository,
    ) {
    }

    public function create(CreateUserDTO $createUserDTO): User
    {
         return $this->repository->createUser($createUserDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): User
    {
        return $this->repository->getUser(
            id: $id,
        );
    }
    public function getUserByIdentifier($identifier): ?User
    {
        $user =  $this->repository->getUserByIdentifier($identifier);
        if(!$user) {
            throw new \Exception(__("validation.user-not-found"), 404);
        }
        return $user;
    }

    public function getAvailableTenantForUser(UuidInterface $id)
    {
        $user = $this->repository->find($id);
         $company_ids =  $this->repository->getWithoutTenancy()->getWherePluck(["global_company_user_id"=>$user->global_company_user_id],"company_id");
         return $this->companyRepository->whereIn("id", $company_ids)->get();
    }
    public function getAdminUsersFromCentralCompanies(int $page = 1, int $perPage = 10): array
    {
       return $this->repository->getAdminUsersFromCentralCompanies($page,$perPage);
    }

    public function export(?array $userIds = null, string $format = 'xlsx')
    {
        $relations = [
            'loginWay',
            'company',
            'companyUser',
            'roles',
            'permissions'
        ];

        $users = $userIds
            ? $this->repository->getUsersWithRelations($userIds, $relations)
            : $this->repository->getUsersWithRelations(null, $relations);

        return new \Modules\User\Exports\UsersExport($users);
    }
}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\User\Repositories\UserRepository;
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository $userRepository,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {
        $user = $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());

        try {
            event(new UserCreated($createCompanyUserDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $companyUsers = $this->repository->withRelations(["companies", 'jobTitle'], $page, $perPage);

        return $companyUsers;
    }

    public function get(UuidInterface $global_id): CompanyUser
    {
        return $this->repository->getCompanyUser(
            global_id:$global_id,
        );
    }

    public function getByEmail(string $email): ?CompanyUser
    {
        return $this->repository->findByEmail(
            email: $email,
        );
    }



}

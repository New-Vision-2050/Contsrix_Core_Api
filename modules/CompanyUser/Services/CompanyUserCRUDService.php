<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO,CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {
        return $this->repository->createCompanyUser($createCompanyUserDTO->toArray(),$companyRoleDTO->toArray());


    }

    public function assignRole(UuidInterface $id , CreateCompanyUserCompanyRoleDTO $createRoleDTO)
    {
        $this->repository->assignRoleCompanyUser($id,$createRoleDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getCompanyUser(
            id: $id,
        );
    }
}

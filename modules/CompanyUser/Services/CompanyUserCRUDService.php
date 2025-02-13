<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use App\Http\Services\RabbitMqService;
use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private RabbitMqService $rabbitMqService,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {
        $user =  $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());



        $this->rabbitMqService->sendMessage("company_user_created", $createCompanyUserDTO->toArray() + $companyRoleDTO->toArray()+["id"=>$user->id]);

        return $user;
    }




    public function list(int $page = 1, int $perPage = 10): array
    {

        $companyUsers = $this->repository->withRelations(["companies"], $page, $perPage);

        return $companyUsers;
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getCompanyUser(
            id: $id,
        );
    }
}

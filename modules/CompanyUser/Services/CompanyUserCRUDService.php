<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

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
        private CompanyRepository     $companyRepository,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {
        $company = $this->companyRepository->findOneBy(["id" => $companyRoleDTO->getCompanyId()]);
        if ($company === null) {
          throw  new \Exception(__("validation.company-not-found"), 404);
        }
        if ($createCompanyUserDTO->getCoutryId() == $company->country_id) {//country of company same country of user must insert identity or passport
            if (request()->identity == null && request()->passport == null) {
                throw new \Exception(__("validation.identity-or-passport-required"), 422);
            }
        } elseif (request()->residence == null && request()->border_number == null && request()->passport == null) {//must insert passport or border_number or residence
            throw new \Exception(__("validation.passport-or-residence-or-border_number-required"), 422);

        }
        return $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());


    }

    public function assignRole(UuidInterface $id, CreateCompanyUserCompanyRoleDTO $createRoleDTO)
    {
        $this->repository->assignRoleCompanyUser($id, $createRoleDTO->toArray());
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

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
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {
        $user = $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());

        try {
            // Get the company ID from the DTO
            $companyId = $companyRoleDTO->getCompanyId();

            // Create additional data for the event
            $additionalData = $createCompanyUserDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id];

            // Dispatch the event with the user, company ID, and additional data
            event(new UserCreated($user, $companyId->toString(), $additionalData));
        }
        catch (\Exception $e){
            // Log the exception
            \Log::error('Failed to dispatch UserCreated event: ' . $e->getMessage());
        }

        return $user;
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $companyUsers = $this->repository->withRelations(["companies",'jobTitle'], $page, $perPage);

        return $companyUsers;
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getCompanyUser(
            id: $id,
        );
    }
    public function getByEmail(string $email): ?CompanyUser
    {
        return $this->repository->findByEmail(
            email: $email,
        );
    }

}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Client;

use App\Exceptions\CustomException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Client\CreateClientCompanyDTO;
use Modules\CompanyUser\DTO\Client\CreateClientDTO;
use Modules\CompanyUser\DTO\Client\UpdateClientDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\User\Repositories\UserRepository;
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\UuidInterface;

class ClientCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
        private CompanyUserCRUDService $companyUserCRUDService
    )
    {
    }

    public function create(CreateClientDTO $createClientDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO, SetUserAddressDTO $userAddressDTO)
    {

        $companyUser = $this->repository->findByEmail($createClientDTO->getEmail());

        $this->companyUserCRUDService->validateDataInsertion($companyUser?->global_id, $companyRoleDTO->getRole(), $createClientDTO->getBranchIds());




        $user = $this->repository->createCompanyUser($createClientDTO->toArray(), $companyRoleDTO->toArray(), $createClientDTO->getBranchIds(), $userAddressDTO->toArray(), $createClientDTO->clientDetailToArray());

//        $emailSent = true;
//        try {
//            $this->companyUserCRUDService->sendEmailAssignToCompanyToUser($user, $companyRoleDTO->getCompanyId());
//        } catch (\Exception $e) {
//            // Log email failure but don't block user creation
//            $emailSent = false;
//        }
//
//        // Store email status for controller to check
//        $user->email_sent = $emailSent;

        //here i do not email up till now
//        $data = [
//            "name" => $userInCompany->name,
//            "company_name" => $userInCompany->company?->name,
//            "domain_name" => $userInCompany->company?->domains()->first()?->domain
//        ];
//        $userInCompany->notify(new SendDomainForUser($data));

        try {
            event(new UserCreated($createClientDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function createClientCompany(CreateClientCompanyDTO $createClientCompanyDTO)
    {
        return $this->userRepository->createClientCompany($createClientCompanyDTO->userId, $createClientCompanyDTO->companyId);
    }

    public function show($id)
    {
        return $this->userRepository->getUserInCurrentCompanyByRole($id, [], CompanyUserRole::CLIENT->value);
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getUserInCurrentCompanyWith([], CompanyUserRole::CLIENT->value, $page, $perPage);

        return $users;
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getCompanyUser(
            id: $id,
        );
    }

    public function getGlobalId(UuidInterface $global_id): CompanyUser
    {
        return $this->repository->getCompanyUserGlobalId(
            global_id: $global_id,
        );
    }

    public function getByEmail(string $email): ?CompanyUser
    {
        return $this->repository->findByEmail(
            email: $email,
        );
    }

    /**
     * Get clients for export
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters, CompanyUserRole::CLIENT->value);
    }

    public function update(UpdateClientDTO $updateClientDTO, SetUserAddressDTO $setUserAddressDTO)
    {
        $user = $this->userRepository->getUserById(
            id: $updateClientDTO->getId(),
        );
        return $this->userRepository->updateClient($user, $updateClientDTO->toArray(), $updateClientDTO->clientDetailToArray(),$setUserAddressDTO->toArray(),$updateClientDTO->getBranchIds());
    }


}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Broker;

use App\Exceptions\CustomException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Broker\UpdateBrokerDTO;
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
use Modules\User\Presenters\BrokerPresenter;
use Modules\User\Repositories\UserRepository;
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\UuidInterface;

class BrokerCRUDService
{


    public function __construct(
        private CompanyUserRepository  $repository,
        private UserRepository         $userRepository,
        private CompanyUserCRUDService $companyUserCRUDService
    )
    {
    }

    public function create(CreateBrokerDTO $createBrokerDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO, SetUserAddressDTO $userAddressDTO)
    {
        $companyUser = $this->repository->findByEmail($createBrokerDTO->getEmail());

        $this->companyUserCRUDService->validateDataInsertion($companyUser?->global_id, $companyRoleDTO->getRole(), $createBrokerDTO->getBranchIds());

        $user = $this->repository->createCompanyUser($createBrokerDTO->toArray(), $companyRoleDTO->toArray(), $createBrokerDTO->getBranchIds(), $userAddressDTO->toArray(), null,$createBrokerDTO->brokerDetailToArray());

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

        try {
            event(new UserCreated($createBrokerDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function update(UpdateBrokerDTO $updateBrokerDTO,SetUserAddressDTO $userAddressDTO)
    {
        $user =$this->userRepository->getUserById($updateBrokerDTO->getId());

        return $this->userRepository->updateBroker($user , $updateBrokerDTO->toArray(),$updateBrokerDTO->brokerDetailToArray(), $userAddressDTO->toArray(), $updateBrokerDTO->getBranchIds());
    }


    public function changeStatus(string $userId, int $status)
    {
        $user = $this->userRepository->getUserById($userId);

        return $this->userRepository->updateStatus($user, CompanyUserRole::BROKER->value, $status);
    }

    public function list(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getUserInCurrentCompanyWith([], CompanyUserRole::BROKER->value, $page, $perPage);

        return $users;
    }


    public function show($id)
    {
        return $this->userRepository->getUserInCurrentCompanyByRole($id, [], CompanyUserRole::BROKER->value);
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

    public function listAsSubEntity(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getBrokerInCurrentCompanyWith($page, $perPage);

        $users['data'] = BrokerPresenter::collection($users['data']);

        return $users;
    }

    /**
     * Get brokers for export
     *
     * @param array $filters
     * @return Collection
     */
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters, CompanyUserRole::BROKER->value);
    }
}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Broker;

use App\Exceptions\CustomException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\User\Repositories\UserRepository;
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\UuidInterface;

class BrokerCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
    )
    {
    }

    public function create(CreateBrokerDTO $createBrokerDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO, SetUserAddressDTO $userAddressDTO)
    {


        $user = $this->repository->createCompanyUser($createBrokerDTO->toArray(), $companyRoleDTO->toArray(), $createBrokerDTO->getBranchIds(), $userAddressDTO->toArray());


        //here i do not email up till now
//        $data = [
//            "name" => $userInCompany->name,
//            "company_name" => $userInCompany->company?->name,
//            "domain_name" => $userInCompany->company?->domains()->first()?->domain
//        ];
//        $userInCompany->notify(new SendDomainForUser($data));

        try {
            event(new UserCreated($createBrokerDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getUserInCurrentCompanyWith([], CompanyUserRole::BROKER->value, $page, $perPage);

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

    public function listAsSubEntity(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getBrokerInCurrentCompanyWith($page, $perPage);

        return $users;
    }
}

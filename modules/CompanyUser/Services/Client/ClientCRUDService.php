<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Client;

use App\Exceptions\CustomException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Client\CreateClientDTO;
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

class ClientCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
    )
    {
    }

    public function create(CreateClientDTO $createClientDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO, SetUserAddressDTO $userAddressDTO)
    {

        $companyUser = $this->repository->findByEmail($createClientDTO->getEmail());

        if ($companyUser != null && $createClientDTO->getBranchIds() != null) {
            $branches = $this->repository->getUserInBranches($companyUser->global_id, $companyRoleDTO->role, $createClientDTO->getBranchIds());
            if (count($branches) > 0 && $companyRoleDTO->getRole() == CompanyUserRole::EMPLOYEE->value) {
                throw new CustomException(__("validation.employee-already-exist"), 400);

            }
            if (count($branches) == count($createClientDTO->getBranchIds())) {
                if ($companyRoleDTO->getRole() == CompanyUserRole::CLIENT->value) {
                    throw new CustomException(__("validation.client-already-exist-in-thies-branches"), 400);
                } elseif ($companyRoleDTO->getRole() == CompanyUserRole::EMPLOYEE->value) {
                    throw new CustomException(__("validation.employee-already-exist"), 400);

                } else {
                    throw new CustomException(__("validation.broker-already-exist-in-thies-branches"), 400);

                }
            }

        }


        $user = $this->repository->createCompanyUser($createClientDTO->toArray(), $companyRoleDTO->toArray(), $createClientDTO->getBranchIds(), $userAddressDTO->toArray(), $createClientDTO->clientDetailToArray());


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


}

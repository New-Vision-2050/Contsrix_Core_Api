<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Employee;

use Modules\CompanyUser\DTO\Employee\UpdateEmployeeDTO;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Exceptions\CustomException;
use RabbitMQ\Jobs\BroadcastMessage;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\User\Repositories\UserRepository;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\User\Presenters\EmployeePresenter;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Employee\CreateEmployeeDTO;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;

class EmployeeCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
        private CompanyUserCRUDService $companyUserCRUDService,
        private CompanyRepository $companyRepository,
    ) {}

    public function create(CreateEmployeeDTO $createEmployeeDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {

        $companyUser = $this->repository->findByEmail($createEmployeeDTO->getEmail());

        $this->companyUserCRUDService->validateDataInsertion($companyUser?->global_id, $companyRoleDTO->getRole(), $createEmployeeDTO->getBranchId());


        $user = $this->repository->createCompanyUser($createEmployeeDTO->toArray(), $companyRoleDTO->toArray(), $createEmployeeDTO->getBranchId());
        $user->fresh();
        $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $user->global_id])->first();
        $companyId = (string)$companyRoleDTO->getCompanyId();
        $company = $this->companyRepository->getCompany(Uuid::fromString($companyId));
        $data = [
            "name" => $userInCompany->name,
            "company_name" => $company->name,
            "domain_name" => "https://".$company->domains()->first()?->domain,
            "serial_no" => $company->serial_no
        ];
        $userInCompany->notify(new SendDomainForUser($data));
        $emailSent = true;
//        try {
//            $this->companyUserCRUDService->sendEmailAssignToCompanyToUser($user, $companyRoleDTO->getCompanyId());
//        } catch (\Exception $e) {
//            // Log email failure but don't block user creation
//            $emailSent = false;
//        }

        // Store email status for controller to check
        $user->email_sent = $emailSent;

        //here i do not email up till now
        //        $data = [
        //            "name" => $userInCompany->name,
        //            "company_name" => $userInCompany->company?->name,
        //            "domain_name" => $userInCompany->company?->domains()->first()?->domain
        //        ];
        //        $userInCompany->notify(new SendDomainForUser($data));

        try {
            event(new UserCreated($createEmployeeDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {
        }

        return $user;
    }



    public function update(UpdateEmployeeDTO $updateEmployeeDTO)
    {
        $userInCompany  = $this->userRepository->getUserById($updateEmployeeDTO->getId());

        $this->userRepository->updateEmployee($userInCompany , $updateEmployeeDTO->toArray());

        return $userInCompany->fresh();
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $users = $this->userRepository->getUserInCurrentCompanyWith([], CompanyUserRole::EMPLOYEE->value, $page, $perPage);

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

        $users = $this->userRepository->getEmployeeInCurrentCompanyWith($page, $perPage);

        $users['data'] = EmployeePresenter::collection($users['data']);

        return $users;
    }
}

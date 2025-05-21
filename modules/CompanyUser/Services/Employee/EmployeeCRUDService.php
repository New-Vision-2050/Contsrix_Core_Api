<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services\Employee;

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
        private UserRepository $userRepository,
    ) {
    }

    public function create(CreateEmployeeDTO $createEmployeeDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {


        $user = $this->repository->createCompanyUser($createEmployeeDTO->toArray(), $companyRoleDTO->toArray(), $createEmployeeDTO->getBranchId());
        if ($createEmployeeDTO->getBranchId())
            $this->userRepository->updateWhere(["global_company_user_id" => $user->global_id, "company_id" => $companyRoleDTO->getCompanyId()], ["management_hierarchy_id" => $createEmployeeDTO->getBranchId()[0]]);



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

        // $users = $this->userRepository->getEmployeeInCurrentCompanyWith($page, $perPage);

        // $users['data'] = EmployeePresenter::collection($users['data']);
        return ['data' => $this->tempEmpoyeeResponse(), 'pagination' => $this->tempPagination()];
        // return $users;
    }

    private function tempEmpoyeeResponse(): array
    {
        return json_decode('[
        {
            "id": "2f7c7255-9b5a-4888-8d09-34e879915ea2",
            "name": "ممتاز نصير",
            "email": "momtaznussair@gmail.com",
            "phone": "+966542138116",
            "job_title": {
                "id": "38df07b6-ae10-4c4e-a560-c698911f3c4d",
                "name": "Head of Department"
            },
            "country": {
                "id": "1",
                "name": "Afghanistan",
                "native": "افغانستان"
            },
            "status": 1,
            "branch": {
                "id": 1,
                "name": "الفرع الرئيسي"
            }
        },
        {
            "id": "b5aefe83-d9c0-4935-831a-71e6108e346b",
            "name": "Admin",
            "email": "admin@constrix-nv.com",
            "phone": "966542138116",
            "job_title": {
                "id": "38df07b6-ae10-4c4e-a560-c698911f3c4d",
                "name": "Head of Department"
            },
            "country": {
                "id": "1",
                "name": "Afghanistan",
                "native": "افغانستان"
            },
            "status": 1,
            "branch": []
        },
        {
            "id": "e105eccb-4d52-4428-8503-de17fc7ebedf",
            "name": "عمرو صالح",
            "email": "amrsaleh@gmail.com",
            "phone": "+966542138114",
            "job_title": {
                "id": "38df07b6-ae10-4c4e-a560-c698911f3c4d",
                "name": "Head of Department"
            },
            "country": {
                "id": "65",
                "name": "Egypt",
                "native": "مصر‎"
            },
            "status": 1,
            "branch": {
                "id": 1,
                "name": "الفرع الرئيسي"
            }
        }
    ]');
    }

    private function tempPagination(): array {
        return json_decode('{
            "page": 1,
            "page_size": 10,
            "next_page": 1,
            "last_page": 1,
            "result_count": 3
        }', true);
    }

}

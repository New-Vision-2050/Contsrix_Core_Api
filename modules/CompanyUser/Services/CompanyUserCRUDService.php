<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use App\Exceptions\CustomException;
use Illuminate\Support\Collection;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Events\UserCreated;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\User\Repositories\UserRepository;
use RabbitMQ\Jobs\BroadcastMessage;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
        private CompanyRepository     $companyRepository
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {

        $user = $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());
        $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $user->global_id, "company_id" => $companyRoleDTO->getCompanyId()]);

        $emailSent = true;
        try {
            $data = [
                "name" => $userInCompany->name,
                "company_name" => $userInCompany->company?->name,
                "domain_name" => "https://".$userInCompany->company?->domains()->first()?->domain,
                "serial_no" => $userInCompany->company?->serial_no
            ];
            $userInCompany->notify(new SendDomainForUser($data));
        } catch (\Exception $e) {
            $emailSent = false;
        }

        // Store email status for controller to check
        $user->email_sent = $emailSent;

        try {
            event(new UserCreated($createCompanyUserDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function sendEmailAssignToCompanyToUser($user , $companyId)
    {
//        try {
            $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $user->global_id])->first();
            $companyId = (string)$companyId;
            $company = $this->companyRepository->getCompany(Uuid::fromString($companyId));
            $data = [
                "name" => $userInCompany->name,
                "company_name" => $company->name,
                "domain_name" => "https://".$company->domains()->first()?->domain,
                "serial_no" => $company->serial_no
            ];
            $userInCompany->notify(new SendDomainForUser($data));
//        } catch (\Exception $e) {
//            // Re-throw the exception so callers can handle it if needed
//            throw $e;
//        }
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $companyUsers = $this->repository->withRelationsFilterByType(["companies", 'jobTitle'], $page, $perPage);

        return $companyUsers;
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

    public function export(?array $companyUserIds = null): string
    {
        $users = $companyUserIds
            ? $this->repository->getIdsWithRelations($companyUserIds, ["companies", "users.company", "country"])
            : $this->repository->getAllWithRelations(["companies", "users.company", "country"]);

        $csvHeader = [
            'ID',
            'Name',
            'Email',
            'Phone',
            "Nationality",
            "Companies",
            "Roles"
        ];

        $csvData = [];
        $csvData[] = $csvHeader;

        foreach ($users as $companyUser) {
            $companies = [];
            $roles = [];
            foreach ($companyUser->users as $user) {
                if ($user->company?->name) {
                    $companies[] = $user->company->name;
                    $companyWithRoles = $companyUser->companies()->where("companies.id", $user->company->id)->get();
                    $tempRoles = "";
                    foreach ($companyWithRoles as $item) {
                        $tempRoles .= CompanyUserRole::lang($item->pivot->role) . " ";
                    }
                    $roles [] = $tempRoles;
                }
            }


            $csvData[] = [
                $companyUser->id,
                $companyUser->name,
                $companyUser->email,
                $companyUser->phone,
                $companyUser->country?->nationality ?? '',
                implode("\n", $companies),
                implode("\n", $roles)

            ];
        }

        return createCSV($csvData);

    }

    public function validateDataInsertion($globalId = null, $role = CompanyUserRole::EMPLOYEE->value, array $branchIds = null)
    {

        if ($globalId != null && $branchIds != null) {
            $branches = $this->repository->getUserInBranches($globalId, $role, $branchIds);
            //check if the employee already exist in Exactly in one branch
            if (count($branches) > 0 && $role == CompanyUserRole::EMPLOYEE->value) {
                throw new CustomException(__("validation.employee-already-exist"), 400);

            }
            //check if the user is already in the branches
            if (count($branches) == count($branchIds) && count(array_intersect( $branches->pluck("management_hierarchy_id")->toArray(),$branchIds)) == count($branches)) {
                if ($role == CompanyUserRole::CLIENT->value) {
                    throw new CustomException(__("validation.client-already-exist-in-thies-branches"), 400);
                }

                elseif ($role == CompanyUserRole::EMPLOYEE->value) {
                    throw new CustomException(__("validation.employee-already-exist"), 400);

                } else {
                    throw new CustomException(__("validation.broker-already-exist-in-thies-branches"), 400);

                }
            }

        }
        return true;

    }



}

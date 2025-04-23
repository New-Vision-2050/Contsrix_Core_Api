<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

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
use Ramsey\Uuid\UuidInterface;

class CompanyUserCRUDService
{


    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository        $userRepository,
    )
    {
    }

    public function create(CreateCompanyUserDTO $createCompanyUserDTO, CreateCompanyUserCompanyRoleDTO $companyRoleDTO)
    {

        $user = $this->repository->createCompanyUser($createCompanyUserDTO->toArray(), $companyRoleDTO->toArray());
        $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $user->global_id, "company_id" => $companyRoleDTO->getCompanyId()]);
        $data = [
            "name" => $userInCompany->name,
            "company_name" => $userInCompany->company?->name,
            "domain_name" => $userInCompany->company?->domains()->first()?->domain
        ];
        $userInCompany->notify(new SendDomainForUser($data));

        try {
            event(new UserCreated($createCompanyUserDTO->toArray() + $companyRoleDTO->toArray() + ["id" => $user->id]));
        } catch (\Exception $e) {

        }

        return $user;
    }


    public function list(int $page = 1, int $perPage = 10): array
    {

        $companyUsers = $this->repository->withRelations(["companies", 'jobTitle'], $page, $perPage);

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


}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\CompanyUser\Commands\AssignRoleCompanyUserCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\User\Repositories\UserRepository;

class AssignRoleCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository $userRepository,
    ) {
    }

    public function handle(AssignRoleCompanyUserCommand $assignRoleCompanyUserCommand)
    {
        $this->repository->assignRoleCompanyUser($assignRoleCompanyUserCommand->getId(), $assignRoleCompanyUserCommand->toArray(),$assignRoleCompanyUserCommand->getBranchIds());
        $companyUser = $this->repository->findOneBy(["id"=>$assignRoleCompanyUserCommand->getId()]);
        $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $companyUser->global_id, "company_id" => $assignRoleCompanyUserCommand->getCompanyId()]);
        $data = [
            "name" => $userInCompany->name,
            "company_name" => $userInCompany->company?->name,
            "domain_name" => $userInCompany->company?->domains()->first()?->domain
        ];
        $userInCompany->notify(new SendDomainForUser($data));

    }
}

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use App\Exceptions\CustomException;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\CompanyUser\Commands\AssignRoleCompanyUserCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Enum\CompanyUserRole;
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

        if ( $assignRoleCompanyUserCommand->getBranchIds() != null) {

            $branches = $this->repository->getUserInBranches($assignRoleCompanyUserCommand->getId(), $assignRoleCompanyUserCommand->getRole(), $assignRoleCompanyUserCommand->getBranchIds());

            if(count($branches) >0 && $assignRoleCompanyUserCommand->getRole() == CompanyUserRole::EMPLOYEE->value )
            {
                throw new CustomException(__("validation.employee-already-exist"),400);

            }

            if(count($branches) == count($assignRoleCompanyUserCommand->getBranchIds())) {
                if($assignRoleCompanyUserCommand->getRole() == CompanyUserRole::CLIENT->value)
                {
                    throw new CustomException(__("validation.client-already-exist-in-thies-branches"),400);
                }
                elseif ($assignRoleCompanyUserCommand->getRole() == CompanyUserRole::EMPLOYEE->value) {
                    throw new CustomException(__("validation.employee-already-exist"),400);

                }
                else{
                    throw new CustomException(__("validation.broker-already-exist-in-thies-branches"),400);

                }
            }

        }
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

<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use App\Exceptions\CustomException;
use Modules\Attendance\Services\AutoAttendanceService;
use Modules\Company\CompanyCore\Notifications\SendDomainForUser;
use Modules\CompanyUser\Commands\AssignRoleCompanyUserCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Modules\CompanyUser\Services\CompanyUserCRUDService;
use Modules\Setting\Commands\UpdateLoginWayCommand;
use Modules\User\Repositories\UserRepository;

class AssignRoleCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
        private UserRepository $userRepository,
        private CompanyUserCRUDService $companyUserCRUDService,
        private AutoAttendanceService $autoAttendanceService
    ) {
    }

    public function handle(AssignRoleCompanyUserCommand $assignRoleCompanyUserCommand)
    {
        $this->companyUserCRUDService->validateDataInsertion($assignRoleCompanyUserCommand->getId() ,$assignRoleCompanyUserCommand->getRole(), $assignRoleCompanyUserCommand->getBranchIds());

        $this->repository->assignRoleCompanyUser($assignRoleCompanyUserCommand->getId(), $assignRoleCompanyUserCommand->toArray(),$assignRoleCompanyUserCommand->getBranchIds());
        $companyUser = $this->repository->findOneBy(["id"=>$assignRoleCompanyUserCommand->getId()]);
        $userInCompany = $this->userRepository->findOneBy(["global_company_user_id" => $companyUser->global_id, "company_id" => $assignRoleCompanyUserCommand->getCompanyId()]);

        $this->autoAttendanceService->generateAttendanceUsers($assignRoleCompanyUserCommand->getCompanyId());

        $data = [
            "name" => $userInCompany->name,
            "company_name" => $userInCompany->company?->name,
            "domain_name" => "https://".$userInCompany->company?->domains()->first()?->domain,
            "serial_no" => $userInCompany->company?->serial_no
        ];
        $userInCompany->notify(new SendDomainForUser($data));

    }
}

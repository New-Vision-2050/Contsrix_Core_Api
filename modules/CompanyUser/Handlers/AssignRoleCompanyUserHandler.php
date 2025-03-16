<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\UpdateLoginWayCommand;
use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class AssignRoleCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UpdateLoginWayCommand $assignRoleCompanyUserCommand)
    {
        $this->repository->assignRoleCompanyUser($assignRoleCompanyUserCommand->getId(), $assignRoleCompanyUserCommand->toArray());
    }
}

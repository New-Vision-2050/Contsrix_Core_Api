<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\UpdateCompanyUserCommand;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class UpdateCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyUserCommand $updateCompanyUserCommand)
    {
        $this->repository->updateCompanyUser($updateCompanyUserCommand->getId(), $updateCompanyUserCommand->toArray());
    }
}

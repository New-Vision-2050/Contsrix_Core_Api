<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\UpdateTimeZoneCompanyUserCommand;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class UpdateTimeZoneCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UpdateTimeZoneCompanyUserCommand $updateTimeZoneCompanyUserCommand)
    {
        $this->repository->updateCompanyUser($updateTimeZoneCompanyUserCommand->getId(), $updateTimeZoneCompanyUserCommand->toArray());
    }
}

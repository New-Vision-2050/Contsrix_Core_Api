<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\UpdateCompanyUserDataInfoCommand;
use Modules\CompanyUser\Events\UserUpdated;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class UpdateCompanyUserDataInfoHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyUserDataInfoCommand $updateCompanyUserCommand)
    {
      return  $this->repository->updateCompanyUserDataInfo($updateCompanyUserCommand->global_id, $updateCompanyUserCommand->toArray());
        // event(new UserUpdated(["id"=>$updateCompanyUserCommand->getId()]+ $updateCompanyUserCommand->toArray()));

    }
}



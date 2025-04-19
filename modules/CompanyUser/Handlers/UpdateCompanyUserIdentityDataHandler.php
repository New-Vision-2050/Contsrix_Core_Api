<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\UpdateIdentityDataCommand;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class UpdateCompanyUserIdentityDataHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UpdateIdentityDataCommand $updateIdentityDataCommand)
    {
      return  $this->repository->updateCompanyUserIdentityData($updateIdentityDataCommand->global_id,$updateIdentityDataCommand->toArray());
        // event(new UserUpdated(["id"=>$updateCompanyUserCommand->getId()]+ $updateCompanyUserCommand->toArray()));

    }
}



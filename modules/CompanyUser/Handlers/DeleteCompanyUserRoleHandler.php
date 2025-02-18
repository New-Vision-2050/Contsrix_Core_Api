<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\DeleteRoleForCompanyUserCommand;
use Modules\CompanyUser\Events\UserDeleted;
use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyUserRoleHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(DeleteRoleForCompanyUserCommand $command)
    {
        $this->repository->deleteCompanyUserRole($command->getId(), $command->getCompanyId(), $command->getRole());
        event(new UserDeleted(["id"=>$command->getId(),"company_id"=> $command->getCompanyId(), "role"=>$command->getRole()]));

    }

}

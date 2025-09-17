<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\DeleteRoleForCompanyUserCommand;
use Modules\CompanyUser\Events\UserRoleDeleted;
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

        try {
            event(new UserRoleDeleted(["id" => $command->getId(), "company_id" => $command->getCompanyId(), "role" => $command->getRole()]));
        } catch (\Exception $e) {
            //do nothing
        }

    }

}

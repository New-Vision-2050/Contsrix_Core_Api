<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Commands\DeleteRoleForUserCommand;
use Modules\CompanyUser\Events\UserRoleDeleted;
use Modules\CompanyUser\Repositories\CompanyUserRepository;

class DeleteUserRoleHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(DeleteRoleForUserCommand $command)
    {
        $this->repository->deleteUserRole($command->getUserId(), $command->getRole());

        try {
            event(new UserRoleDeleted(["user_id" => $command->getUserId(), "role" => $command->getRole()]));
        } catch (\Exception $e) {
            //do nothing
        }
    }
}

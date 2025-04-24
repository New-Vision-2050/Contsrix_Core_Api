<?php

declare(strict_types=1);

namespace Modules\User\Handlers;

use Modules\RoleAndPermission\Commands\AssignPermissionToRoleCommand;
use Modules\User\Commands\AssignRoleForUserCommand;
use Modules\User\Commands\UpdateUserCommand;
use Modules\User\Repositories\UserRepository;

class AssignRoleForUserHandler
{
    public function __construct(
        private UserRepository $repository,
    ) {
    }

    public function handle(AssignRoleForUserCommand $assignRoleForUserCommand)
    {
        $this->repository->assignRole($assignRoleForUserCommand->getId(), $assignRoleForUserCommand->geRoles());
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Commands\AssignPermissionToRoleCommand;
use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Modules\RoleAndPermission\Repositories\RoleRepository;

class AssignPermissionsToRoleHandler
{
    public function __construct(
        private RoleRepository $repository,
    ) {
    }

    public function handle(AssignPermissionToRoleCommand $assignPermissionToRoleCommand)
    {
        $this->repository->givePermissionsToRole($assignPermissionToRoleCommand->getId(), $assignPermissionToRoleCommand->getPermissions());
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;

class UpdateRoleAndPermissionHandler
{
    public function __construct(
        private RoleAndPermissionRepository $repository,
    ) {
    }

    public function handle(UpdateRoleAndPermissionCommand $updateRoleAndPermissionCommand)
    {
        $this->repository->updateRoleAndPermission($updateRoleAndPermissionCommand->getId(), $updateRoleAndPermissionCommand->toArray());
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Commands\UpdatePermissionCommand;
use Modules\RoleAndPermission\Commands\UpdateRoleAndPermissionCommand;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;

class UpdatePermissionHandler
{
    public function __construct(
        private PermissionRepository $repository,
    ) {
    }

    public function handle(UpdatePermissionCommand $updatePermissionCommand)
    {
        $this->repository->updatePermission($updatePermissionCommand->getId(), $updatePermissionCommand->toArray());
    }
}

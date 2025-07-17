<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Commands\UpdateRoleCommand;
use Modules\RoleAndPermission\Repositories\RoleRepository;

class UpdateRoleHandler
{
    public function __construct(
        private RoleRepository $repository,
    ) {
    }

    public function handle(UpdateRoleCommand $updateRoleCommand)
    {
        $this->repository->updateRole($updateRoleCommand->getId(), $updateRoleCommand->toArray(), $updateRoleCommand->getPermissions());
    }
}

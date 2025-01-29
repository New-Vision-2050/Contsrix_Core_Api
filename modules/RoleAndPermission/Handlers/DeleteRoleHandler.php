<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;
use Modules\RoleAndPermission\Repositories\RoleRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteRoleHandler
{
    public function __construct(
        private RoleRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteRole($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteRoleAndPermissionHandler
{
    public function __construct(
        private RoleAndPermissionRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteRoleAndPermission($id);
    }
}

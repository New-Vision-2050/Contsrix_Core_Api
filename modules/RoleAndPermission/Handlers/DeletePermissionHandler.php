<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Handlers;

use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePermissionHandler
{
    public function __construct(
        private PermissionRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePermission($id);
    }
}

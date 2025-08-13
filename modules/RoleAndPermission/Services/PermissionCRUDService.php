<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Modules\RoleAndPermission\DTO\CreatePermissionDTO;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Repositories\PermissionRepository;
use Ramsey\Uuid\UuidInterface;

class PermissionCRUDService
{
    public function __construct(
        private PermissionRepository $repository,
    ) {
    }

    public function create(CreatePermissionDTO $createPermissionDTO): Permission
    {
         return $this->repository->createPermission($createPermissionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Permission
    {
        return $this->repository->getPermission(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\RoleAndPermission\DTO\CreateRoleAndPermissionDTO;
use Modules\RoleAndPermission\Models\RoleAndPermission;
use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;
use Ramsey\Uuid\UuidInterface;

class PermissionCRUDService
{
    public function __construct(
        private RoleAndPermissionRepository $repository,
    ) {
    }

    public function create(CreateRoleAndPermissionDTO $createRoleAndPermissionDTO): RoleAndPermission
    {
         return $this->repository->createRoleAndPermission($createRoleAndPermissionDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): RoleAndPermission
    {
        return $this->repository->getRoleAndPermission(
            id: $id,
        );
    }
}

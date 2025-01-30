<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\RoleAndPermission\DTO\CreateRoleAndPermissionDTO;
use Modules\RoleAndPermission\DTO\CreateRoleDTO;
use Modules\RoleAndPermission\Models\Role;
use Modules\RoleAndPermission\Repositories\RoleAndPermissionRepository;
use Modules\RoleAndPermission\Repositories\RoleRepository;
use Ramsey\Uuid\UuidInterface;

class RoleCRUDService
{
    public function __construct(
        private RoleRepository $repository,
    ) {
    }

    public function create(CreateRoleDTO $createRoleDTO): Role
    {
         return $this->repository->createRole($createRoleDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Role
    {
        return $this->repository->getRole(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Collection;
use Modules\RoleAndPermission\DTO\CreatePermissionForRoleDTO;
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

    public function create(CreateRoleDTO $createRoleDTO,CreatePermissionForRoleDTO $createPermissionForRoleDTO): Role
    {
         return $this->repository->createRole($createRoleDTO->toArray(),$createPermissionForRoleDTO->toArray());
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

    /**
     * Set the status of a role.
     *
     * @param UuidInterface|string $id The ID of the role.
     * @param bool $status The new status.
     * @return Role
     */
    public function setStatus($id, bool $status): Role
    {
        if (is_string($id)) {
            $id = \Ramsey\Uuid\Uuid::fromString($id);
        }

        $role = $this->repository->getRole($id);
        $this->repository->update($id, ['status' => $status]);

        return $role->refresh();
    }
}

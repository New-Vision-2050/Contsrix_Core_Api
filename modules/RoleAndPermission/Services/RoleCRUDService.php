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
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;

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
     * @throws ValidationException If attempting to deactivate a role with assigned users
     */
    public function setStatus($id,  $status): Role
    {
        if (is_string($id)) {
            $id = \Ramsey\Uuid\Uuid::fromString($id);
        }

        $role = $this->repository->getRole($id);

        // If trying to deactivate the role, check if any users have this role
        if ($status === false) {
            // Check if role has any users assigned using repository method
            if ($this->repository->roleHasUsers($id)) {
                throw ValidationException::withMessages([
                    'status' => [__('validation.custom.role.cannot_deactivate')]
                ]);
            }
        }

        $this->repository->update($id, ['status' => $status]);

        return $role->refresh();
    }
}

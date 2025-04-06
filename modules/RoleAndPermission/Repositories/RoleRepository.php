<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\RoleAndPermission\Models\Role;
use Ramsey\Uuid\UuidInterface;

/**
 * @property Role $model
 * @method Role findOneOrFail($id)
 * @method Role findOneByOrFail(array $data)
 */
class RoleRepository extends BaseRepository
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function getRoleList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getRole(UuidInterface $id): Role
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createRole(array $roleData , array $permissions): Role
    {
        return $this->create($roleData)->syncPermissions($permissions);
    }

    public function updateRole(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteRole(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function givePermissionsToRole(UuidInterface $id ,array $permissions):Role
    {
        $role = $this->getRole($id);
        $role->syncPermissions($permissions);
        return $role;
    }

}

<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\RoleAndPermission\Models\Role;
use Ramsey\Uuid\UuidInterface;
use function Symfony\Component\Translation\t;

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

    public function createRole(array $roleData , ?array $permissions): Role
    {
        return $this->create($roleData)->syncPermissions($permissions);
    }

    public function updateRole(UuidInterface $id, array $data , ?array $permissions): bool
    {

        try {
            DB::beginTransaction();
            $this->update($id, $data);
            $this->givePermissionsToRole($id, $permissions);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception(__("validation.update-not-successful"), 500);
        }
        return true;

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

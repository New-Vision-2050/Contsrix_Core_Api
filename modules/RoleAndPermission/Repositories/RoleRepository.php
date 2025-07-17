<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\RoleAndPermission\DTO\RoleWidgetsDataDTO;
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

    public function createRole(array $roleData , ?array $permissions): Role
    {
        $role = $this->create($roleData);
        if (!$role) {
            throw new \Exception('Role creation failed');
        }
        return $role->syncPermissions($permissions);
    }

    public function updateRole(UuidInterface $id, array $data , ?array $permissions): bool
    {

        try {
            DB::beginTransaction();
            $updateResult = $this->update($id, $data);
            if (!$updateResult) {
                throw new \Exception('Role update failed');
            }
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

    /**
     * Check if a role has any assigned users
     *
     * @param UuidInterface $id The role ID
     * @return bool True if the role has any assigned users, false otherwise
     */
    public function roleHasUsers(UuidInterface $id): bool
    {
        $roleId = $id->toString();
        $count = DB::table('model_has_roles')
            ->where('role_id', $roleId)
            ->count();

        return $count > 0;
    }

    public function getRoleWidgetsData(): RoleWidgetsDataDTO
    {
        $totalRoles = $this->model->query()->count();
        $activeRoles = $this->model->query()->where('status', 1)->count();
        $inactiveRoles = $this->model->query()->where('status', 0)->count();
        // TODO: Confirm the definition of a 'Main Role'. Assuming roles with no company_id for now.
        $mainRoles = $this->model->query()->whereNull('company_id')->count();

        return RoleWidgetsDataDTO::fromArray([
            'total_roles' => $totalRoles,
            'main_roles' => $mainRoles,
            'active_roles' => $activeRoles,
            'inactive_roles' => $inactiveRoles,
        ]);
    }
}

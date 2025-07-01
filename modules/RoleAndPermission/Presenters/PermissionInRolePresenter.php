<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\RoleAndPermission\Models\RoleAndPermission;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Nwidart\Modules\Collection;

class PermissionInRolePresenter extends AbstractPresenter
{

    private Permission $rolePermissions;

    public function __construct(Permission $permissions )
    {
        $this->rolePermissions = $permissions;
    }

    protected function present(bool $isListing = false): array
    {

        return [
            'id' => $this->rolePermissions->id,
            'name' => $this->rolePermissions->name,
            'is_active' => $this->rolePermissions->is_active,
        ];
    }
}

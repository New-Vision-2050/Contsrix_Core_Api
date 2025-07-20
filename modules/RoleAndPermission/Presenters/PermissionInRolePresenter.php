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

    private Permission $permission;

    public function __construct(Permission $permission )
    {
        $this->permission = $permission;
    }

    protected function present(): array
    {

        return [
            'id' => $this->permission->id,
            'name' => $this->permission->name,
            'is_active' => $this->permission->is_active,
        ];
    }
}

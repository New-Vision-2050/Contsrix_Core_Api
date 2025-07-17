<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use BasePackage\Shared\Presenters\AbstractPresenter;

class RolePresenter extends AbstractPresenter
{
    private Role $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    protected function present(bool $isListing = false): array
    {

        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            "status"=>$this->role->status,
            "permission_count"=>$this->role->permissions()->count(),
        ];
    }
}

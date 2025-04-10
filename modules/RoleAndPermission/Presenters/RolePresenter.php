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
        $permissions = Permission::get();
        $modified = [];
        foreach ($permissions as $permission) {
            $permission->is_active = $this->role->permissions()->where("name", $permission->name)->first() ? true : false;
            $modified[] = ["id" => $permission->id, "name" => $permission->name,"permission"=>explode('.', $permission->name)[1], "is_active" => $permission->is_active];
        }
        $modified = collect($modified)->groupBy(function($query) {
        return explode('.', $query["name"])[0];
    })->toArray();;
        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            "permissions" => $modified
        ];
    }
}

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

            // Extract the permission name parts
            $nameParts = explode('.', $permission->name);

            // Initialize the translated name
            $translatedName = '';

            // Apply translation logic like the Blade template
            if (count($nameParts) >= 2) {
                // Skip the first part (module name) and translate the rest
                for ($i = 0; $i < count($nameParts); $i++) {
                    $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
                }
            } elseif (count($nameParts) == 1) {
                $translatedName = __('names.' . $nameParts[0]);
            } else {
                $translatedName = __('names.' . $permission->name);
            }

            $modified[] = [
                "id" => $permission->id,
                "key" => $permission->name,
                "name" => $translatedName,
                "is_active" => $permission->is_active
            ];
        }
        $modified = collect($modified)->groupBy(function($query) {
            return explode('.', $query["key"])[0];
        })->toArray();
        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            "permissions" => $modified
        ];
    }
}

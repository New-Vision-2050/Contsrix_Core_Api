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
                for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                    $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
                }
            } elseif (count($nameParts) == 1) {
                $translatedName = __('names.' . $nameParts[0]);
            } else {
                $translatedName = __('names.' . $permission->name);
            }
            $parts = explode('.', $permission->name);
            $modified[] = [
                "id" => $permission->id,
                "key" => $permission->name,
                "type" => $parts[count($parts) - 1],
                "name" => $translatedName,
                "is_active" => $permission->is_active
            ];
        }

        // First group by the first part of the name (module)
        $groupedByModule = collect($modified)->groupBy(function ($query) {
            $parts = explode('.', $query["key"]);
            return isset($parts[0]) ? $parts[0] : 'other';
        });

        // Then for each module group, group again by the second part (action)
        $nestedGroups = $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item["key"]);
                return isset($parts[1]) ? $parts[1] : 'other';
            });
        })->toArray();

        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            "permissions" => $nestedGroups
        ];
    }
}

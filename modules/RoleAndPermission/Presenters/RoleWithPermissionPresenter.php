<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\RoleAndPermission\Services\PermissionLookupService;

class RoleWithPermissionPresenter extends AbstractPresenter
{
    private Role $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    protected function present(bool $isListing = false): array
    {
        $permissions =app(PermissionLookupService::class)->getPermissionsForCompany();
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
            return isset($parts[0]) ? __('names.' . $parts[0]) : 'other';
        });

        // Then for each module group, group again by the second part (action)
        $nestedGroups =  $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item["key"]);
                if (isset($parts[1]) && str_contains($parts[1], '*')) {
                    $subParts = explode("*", $parts[1]);
                    if (isset($subParts[1])) {
                        // Check if the part after asterisk is a UUID
                        $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $subParts[1]);
                        if ($isUuid) {
                            // If it's a UUID, group by the part before asterisk
                            return isset($subParts[0]) ?  $subParts[0] : 'other';
                        } else {
                            // If it's not a UUID, group by the part after asterisk
                            return __('names.' . $subParts[0]);
                        }
                    }
                }
                return isset($parts[1]) ? __('names.' . explode("*", $parts[1])[0]) : 'other';
            })->map(function ($subGroup, $action) {
                return collect($subGroup)->groupBy(function ($item) {
                    $parts = explode('.', $item["key"]);
                    if (isset($parts[1]) && str_contains($parts[1], '*')) {
                        $subParts = explode("*", $parts[1]);
                        if (isset($subParts[1])) {
                            // Check if the part after asterisk is a UUID
                            $isUuid = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $subParts[1]);
                            if ($isUuid) {
                                // If it's a UUID, group by the part before asterisk
                                return isset($subParts[0]) ?  $subParts[0] : 'other';
                            } else {
                                // If it's not a UUID, group by the part after asterisk
                                return __('names.' . $subParts[1]);
                            }
                        }
                    }
                    return 'other';
                });
            });
        })->toArray();

        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            "permissions" => $nestedGroups
        ];
    }
}

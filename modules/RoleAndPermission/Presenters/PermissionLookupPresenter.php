<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Presenters;

use Illuminate\Support\Collection;

class PermissionLookupPresenter
{
    public function present(Collection $permissions): array
    {
        $modified = [];
        foreach ($permissions as $permission) {
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
            ];
        }

        // First group by the first part of the name (module)
        $groupedByModule = collect($modified)->groupBy(function ($query) {
            $parts = explode('.', $query["key"]);
            return isset($parts[0]) ? __('names.' . $parts[0]): 'other';
        });

        // Then for each module group, group again by the second part (action)
        return $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item["key"]);
                return isset($parts[1]) ? __('names.' . $parts[1]) : 'other';
            });
        })->toArray();
    }
}

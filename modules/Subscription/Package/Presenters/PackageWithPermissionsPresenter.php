<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\RoleAndPermission\Services\PermissionLookupService;
use Modules\Subscription\Package\Models\Package;

class PackageWithPermissionsPresenter extends AbstractPresenter
{
    public function __construct(private Package $package)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $permissions =app(PermissionLookupService::class)->getPermissionsForCompany();
        $modified = [];
        foreach ($permissions as $permission) {
            $permission->is_active = $this->package->permissions()->where("name", $permission->name)->first() ? true : false;

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
        $nestedGroups = $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item["key"]);
                return isset($parts[1]) ? __('names.' . $parts[1]) : 'other';
            });
        })->toArray();

        return [
            'id' => $this->package->id,
            'name' => $this->package->name,
            'permissions' => $nestedGroups,
        ];
    }
}

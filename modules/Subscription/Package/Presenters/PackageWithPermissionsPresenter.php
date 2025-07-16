<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Subscription\Package\Models\Package;

class PackageWithPermissionsPresenter extends AbstractPresenter
{
    public function __construct(private Package $package)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $permissions = $this->package->permissions()->get()->map(function ($permission) {
            $nameParts = explode('.', $permission->name);
            $translatedName = '';

            if (count($nameParts) >= 2) {
                for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                    $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
                }
            } elseif (count($nameParts) == 1) {
                $translatedName = __('names.' . $nameParts[0]);
            } else {
                $translatedName = __('names.' . $permission->name);
            }

            $parts = explode('.', $permission->name);
            return [
                'id' => $permission->id,
                'key' => $permission->name,
                'type' => $parts[count($parts) - 1],
                'name' => $translatedName,
                "limit" => $permission->pivot?->limit??0,
                'is_active' => true, // All permissions in the pivot table are active for the package
            ];
        });

        $groupedByModule = $permissions->groupBy(function ($query) {
            $parts = explode('.', $query['key']);
            return __('names.' . $parts[0]) ?? 'other';
        });

        $nestedGroups = $groupedByModule->map(function ($group, $module) {
            return collect($group)->groupBy(function ($item) {
                $parts = explode('.', $item['key']);
                return __('names.' . $parts[1]) ?? 'other';
            });
        })->toArray();

        return [
            'id' => $this->package->id,
            'name' => $this->package->name,
            'permissions' => $nestedGroups,
        ];
    }
}

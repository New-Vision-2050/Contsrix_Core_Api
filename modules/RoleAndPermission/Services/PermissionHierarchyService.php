<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Modules\RoleAndPermission\Models\Permission;
use Illuminate\Support\Collection;

class PermissionHierarchyService
{
    /**
     * Get permissions parsed from permission names following program.subEntity.action pattern
     *
     * @return array
     */
    public function getPermissionsFromNames(): array
    {
        // Get all permissions
        $permissions = Permission::where('is_active', true)->get();

        // Group permissions by program and sub-entity
        $groupedPermissions = $this->groupPermissionsByHierarchy($permissions);

        $result = [];

        foreach ($groupedPermissions as $programSlug => $subEntityGroups) {
            // Create program structure from permission name
            $programData = [
                'id' => $programSlug,
                'name' => $this->getTranslatedName($programSlug),
                'slug' => $programSlug,
                'is_active' => 1,
                'sub_entities' => [],
                'children' => []
            ];

            foreach ($subEntityGroups as $subEntitySlug => $actions) {
                // Create sub-entity structure from permission name
                $subEntityData = [
                    'id' => $subEntitySlug,
                    'name' => $this->getTranslatedName($subEntitySlug),
                    'slug' => $subEntitySlug,
                    'main_program_id' => $programSlug,
                    'super_entity' => $programSlug,
                    'origin_super_entity' => $programSlug,
                    'is_active' => 1,
                    'children' => []
                ];

                $programData['sub_entities'][] = $subEntityData;
            }

            $result[] = $programData;
        }

        return $result;
    }

    /**
     * Group permissions by program and sub-entity from permission names
     *
     * @param Collection $permissions
     * @return array
     */
    private function groupPermissionsByHierarchy(Collection $permissions): array
    {
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);

            if (count($parts) >= 3) {
                $program = $parts[0];
                $subEntity = $parts[1];
                $action = $parts[2];

                if (!isset($grouped[$program])) {
                    $grouped[$program] = [];
                }

                if (!isset($grouped[$program][$subEntity])) {
                    $grouped[$program][$subEntity] = [];
                }

                $grouped[$program][$subEntity][] = $action;
            }
        }

        return $grouped;
    }

    /**
     * Get translated name using Laravel translation system
     *
     * @param string $name
     * @return string
     */
    private function getTranslatedName(string $name): string
    {
        $translatedName = '';
        $nameParts = explode('.', $name);

        if (count($nameParts) >= 2) {
            // Skip the first part (module name) and translate the rest
            for ($i = count($nameParts) - 1; $i >= 1; $i--) {
                $translatedName .= ($translatedName ? ' ' : '') . __('names.' . $nameParts[$i]);
            }
        } elseif (count($nameParts) == 1) {
            $translatedName = __('names.' . $nameParts[0]);
        } else {
            $translatedName = __('names.' . $name);
        }

        return $translatedName;
    }

    /**
     * Get permissions with detailed action information
     *
     * @return array
     */
    public function getDetailedPermissionsHierarchy(): array
    {
        $permissions = Permission::where('status', true)->get();
        $groupedPermissions = $this->groupPermissionsByHierarchy($permissions);

        $result = [];

        foreach ($groupedPermissions as $programSlug => $subEntityGroups) {
            $programData = [
                'id' => $programSlug,
                'name' => $this->getTranslatedName($programSlug),
                'slug' => $programSlug,
                'is_active' => 1,
                'sub_entities' => [],
                'children' => []
            ];

            foreach ($subEntityGroups as $subEntitySlug => $actions) {
                $subEntityData = [
                    'id' => $subEntitySlug,
                    'name' => $this->getTranslatedName($subEntitySlug),
                    'slug' => $subEntitySlug,
                    'main_program_id' => $programSlug,
                    'super_entity' => $programSlug,
                    'origin_super_entity' => $programSlug,
                    'is_active' => 1,
                    'actions' => $actions, // Include available actions
                    'children' => []
                ];

                $programData['sub_entities'][] = $subEntityData;
            }

            $result[] = $programData;
        }

        return $result;
    }
}

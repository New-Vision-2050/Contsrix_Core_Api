<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Helpers;

use Modules\RoleAndPermission\Enums\Permission;

/**
 * Helper class for working with grouped permissions
 */
class PermissionHelper
{
    /**
     * Get permission string using dot notation
     * 
     * Usage: PermissionHelper::permission('users.user.view')
     * 
     * @param string $permission Dot notation permission (e.g., 'users.user.view')
     * @return string|null
     */
    public static function permission(string $permission): ?string
    {
        $parts = explode('.', $permission);
        
        if (count($parts) !== 3) {
            return null;
        }

        [$module, $submodule, $action] = $parts;
        
        return Permission::get($module, $submodule, $action);
    }

    /**
     * Check if permission exists using dot notation
     * 
     * @param string $permission Dot notation permission
     * @return bool
     */
    public static function exists(string $permission): bool
    {
        $parts = explode('.', $permission);
        
        if (count($parts) !== 3) {
            return false;
        }

        [$module, $submodule, $action] = $parts;
        
        return Permission::exists($module, $submodule, $action);
    }

    /**
     * Get multiple permissions using dot notation
     * 
     * @param array $permissions Array of dot notation permissions
     * @return array
     */
    public static function permissions(array $permissions): array
    {
        $result = [];
        
        foreach ($permissions as $permission) {
            $value = self::permission($permission);
            if ($value !== null) {
                $result[] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Get all permissions for a module
     * 
     * @param string $module Module name
     * @return array
     */
    public static function modulePermissions(string $module): array
    {
        $permissions = Permission::getModulePermissions($module);
        $flat = [];

        foreach ($permissions as $submodule => $actions) {
            foreach ($actions as $permission) {
                $flat[] = $permission;
            }
        }

        return $flat;
    }

    /**
     * Get all permissions for a submodule
     * 
     * @param string $module Module name
     * @param string $submodule Submodule name
     * @return array
     */
    public static function submodulePermissions(string $module, string $submodule): array
    {
        return array_values(Permission::getSubmodulePermissions($module, $submodule));
    }

    /**
     * Get CRUD permissions for a submodule
     * 
     * @param string $module Module name
     * @param string $submodule Submodule name
     * @return array
     */
    public static function crudPermissions(string $module, string $submodule): array
    {
        $crud = ['view', 'list', 'create', 'edit', 'update', 'delete'];
        $permissions = [];

        foreach ($crud as $action) {
            $permission = Permission::get($module, $submodule, $action);
            if ($permission) {
                $permissions[$action] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * Generate permission arrays for route groups
     * 
     * @param string $module Module name
     * @param string $submodule Submodule name
     * @param array $actions Actions to include (default: all available)
     * @return array
     */
    public static function forRoutes(string $module, string $submodule, array $actions = []): array
    {
        $availableActions = Permission::getActions($module, $submodule);
        $targetActions = empty($actions) ? $availableActions : array_intersect($actions, $availableActions);
        
        $permissions = [];
        foreach ($targetActions as $action) {
            $permissions[$action] = Permission::get($module, $submodule, $action);
        }

        return $permissions;
    }

    /**
     * Get permissions formatted for select options in UI
     * 
     * @param string|null $module Optional module filter
     * @return array
     */
    public static function forSelect(?string $module = null): array
    {
        $permissions = $module ? Permission::getModulePermissions($module) : Permission::getAllPermissions();
        $options = [];

        foreach ($permissions as $moduleKey => $submodules) {
            if ($module && $moduleKey !== $module) {
                continue;
            }

            $moduleLabel = ucwords(str_replace('-', ' ', $moduleKey));
            
            foreach ($submodules as $submoduleKey => $actions) {
                $submoduleLabel = ucwords(str_replace('-', ' ', $submoduleKey));
                
                foreach ($actions as $action => $permission) {
                    $actionLabel = ucwords(str_replace('-', ' ', $action));
                    $options[$permission] = "{$moduleLabel} - {$submoduleLabel} - {$actionLabel}";
                }
            }
        }

        return $options;
    }

    /**
     * Validate permission structure
     * 
     * @param array $permissions Permissions array to validate
     * @return array Validation results
     */
    public static function validateStructure(array $permissions): array
    {
        $errors = [];
        $warnings = [];

        foreach ($permissions as $module => $submodules) {
            if (!is_array($submodules)) {
                $errors[] = "Module '{$module}' should contain an array of submodules";
                continue;
            }

            foreach ($submodules as $submodule => $actions) {
                if (!is_array($actions)) {
                    $errors[] = "Submodule '{$module}.{$submodule}' should contain an array of actions";
                    continue;
                }

                foreach ($actions as $action => $permission) {
                    // Check if permission follows the expected pattern
                    $expectedPermission = "{$module}.{$submodule}.{$action}";
                    if ($permission !== $expectedPermission) {
                        $warnings[] = "Permission '{$permission}' doesn't follow expected pattern '{$expectedPermission}'";
                    }
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Search permissions by keyword
     * 
     * @param string $keyword Search keyword
     * @return array
     */
    public static function search(string $keyword): array
    {
        $permissions = Permission::getAllPermissions();
        $results = [];
        $keyword = strtolower($keyword);

        foreach ($permissions as $module => $submodules) {
            foreach ($submodules as $submodule => $actions) {
                foreach ($actions as $action => $permission) {
                    // Search in module, submodule, action, or permission string
                    if (str_contains(strtolower($module), $keyword) ||
                        str_contains(strtolower($submodule), $keyword) ||
                        str_contains(strtolower($action), $keyword) ||
                        str_contains(strtolower($permission), $keyword)) {
                        
                        $results[] = [
                            'module' => $module,
                            'submodule' => $submodule,
                            'action' => $action,
                            'permission' => $permission,
                            'display' => ucwords(str_replace('-', ' ', "{$module} {$submodule} {$action}"))
                        ];
                    }
                }
            }
        }

        return $results;
    }
}

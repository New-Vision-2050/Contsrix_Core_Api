<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Facades\File;

/**
 * Service to automatically merge permission configurations from all modules
 */
class PermissionConfigService
{
    /**
     * Get merged permissions from all modules
     */
    public static function getMergedPermissions(): array
    {
        $allPermissions = [];
        $modulesPath = base_path('modules');
        
        if (!File::exists($modulesPath)) {
            return [];
        }

        // Get all module directories
        $modules = File::directories($modulesPath);
        
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $permissionFile = $modulePath . '/Config/permissions.php';
            
            if (File::exists($permissionFile)) {
                try {
                    $modulePermissions = include $permissionFile;
                    
                    // Validate that the file returns an array with 'permissions' key
                    if (is_array($modulePermissions) && isset($modulePermissions['permissions'])) {
                        $allPermissions = array_merge($allPermissions, $modulePermissions['permissions']);
                    }
                } catch (\Throwable $e) {
                    // Log error but continue processing other modules
                    \Log::warning("Failed to load permissions from module {$moduleName}: " . $e->getMessage());
                }
            }
        }

        return $allPermissions;
    }

    /**
     * Get the complete merged configuration structure
     */
    public static function getMergedConfig(): array
    {
        return [
            'permissions' => self::getMergedPermissions()
        ];
    }

    /**
     * Get permissions for a specific module
     */
    public static function getModulePermissions(string $moduleName): array
    {
        $permissionFile = base_path("modules/{$moduleName}/Config/permissions.php");
        
        if (!File::exists($permissionFile)) {
            return [];
        }

        try {
            $modulePermissions = include $permissionFile;
            return is_array($modulePermissions) && isset($modulePermissions['permissions']) 
                ? $modulePermissions['permissions'] 
                : [];
        } catch (\Throwable $e) {
            \Log::warning("Failed to load permissions from module {$moduleName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cache the merged permissions for better performance
     */
    public static function getCachedMergedPermissions(): array
    {
        $cacheKey = 'merged_module_permissions';
        
        return cache()->remember($cacheKey, 3600, function () {
            return self::getMergedPermissions();
        });
    }

    /**
     * Clear the permissions cache
     */
    public static function clearCache(): void
    {
        cache()->forget('merged_module_permissions');
    }

    /**
     * Get all modules that have permission configurations
     */
    public static function getModulesWithPermissions(): array
    {
        $modulesWithPermissions = [];
        $modulesPath = base_path('modules');
        
        if (!File::exists($modulesPath)) {
            return [];
        }

        $modules = File::directories($modulesPath);
        
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $permissionFile = $modulePath . '/Config/permissions.php';
            
            if (File::exists($permissionFile)) {
                $modulesWithPermissions[] = $moduleName;
            }
        }

        return $modulesWithPermissions;
    }
}

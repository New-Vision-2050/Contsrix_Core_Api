<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Enums;

/**
 * Project Permission Enum
 * 
 * Dynamically loads permission values from config file
 * Usage: ProjectPermission::PROJECT_EMPLOYEE_CREATE()
 * Returns: 'project-management.project-management*employee.create'
 */
class ProjectPermission
{
    /**
     * Magic method to get permission value from config
     * 
     * @param string $name Permission key (e.g., 'PROJECT_EMPLOYEE_CREATE')
     * @param array $arguments
     * @return string Permission value from config
     */
    public static function __callStatic(string $name, array $arguments): string
    {
        $permissions = config('project-management.permissions', []);
        
        if (!isset($permissions[$name])) {
            throw new \InvalidArgumentException("Permission key '{$name}' not found in project-management config");
        }
        
        return $permissions[$name];
    }

    /**
     * Get all project permissions from config
     * 
     * @return array
     */
    public static function all(): array
    {
        return config('project-management.permissions', []);
    }

    /**
     * Check if a permission key exists
     * 
     * @param string $key
     * @return bool
     */
    public static function exists(string $key): bool
    {
        $permissions = config('project-management.permissions', []);
        return isset($permissions[$key]);
    }

    /**
     * Get permission value by key
     * 
     * @param string $key
     * @return string|null
     */
    public static function get(string $key): ?string
    {
        $permissions = config('project-management.permissions', []);
        return $permissions[$key] ?? null;
    }

    /**
     * Get all permission keys
     * 
     * @return array
     */
    public static function keys(): array
    {
        return array_keys(config('project-management.permissions', []));
    }

    /**
     * Get all permission values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_values(config('project-management.permissions', []));
    }
}

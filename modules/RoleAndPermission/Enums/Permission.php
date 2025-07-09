<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Enums;

use ReflectionClass;

/**
 * @method static string SUPER_ADMIN()
 * @method static string ADMIN()
 */
enum Permission: string
{
    public static function __callStatic($name, $arguments)
    {
        $permissions = config('permissions.permissions');
        if (!isset($permissions[$name])) {
            throw new \Exception("Permission constant '{$name}' not found in config file.");
        }

        return $permissions[$name];
    }

    public static function all(): array
    {
        return array_values(config('permissions.permissions'));
    }

    public static function getAllPermissions(): array
    {
        $reflectionClass = new ReflectionClass(self::class);

        return $reflectionClass->getConstants();
    }
}

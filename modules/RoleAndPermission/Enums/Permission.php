<?php

namespace Modules\RoleAndPermission\Enums;

use Illuminate\Support\Facades\Config;

class Permission
{
    /**
     * Dynamically retrieve a permission string from the config file.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \Exception
     */
    public static function __callStatic($name, $arguments)
    {
        $key = 'permissions.permissions.' . $name;
        $permission = Config::get($key);

        if (!$permission) {
            throw new \Exception("Permission constant '{$name}' not found in config file.");
        }

        return $permission;
    }

    /**
     * Get all permission values.
     *
     * @return array
     */
    public static function all(): array
    {
        return array_values(Config::get('permissions.permissions', []));
    }
}

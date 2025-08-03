<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PermissionFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }

    public function search($search)
    {
        return $this->where('name', $search);
    }
}

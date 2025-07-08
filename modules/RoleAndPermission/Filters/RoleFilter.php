<?php

declare(strict_types=1);

namespace Modules\RoleAndPermission\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class RoleFilter extends SearchModelFilter
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

    public function status($satus)
    {
        return $this->where('status');
    }

    public function employeeName($employeeName)
    {
        return $this->whereHas('users', function ($q) use ($employeeName) {
            $q->where('name', 'like', '%' . $employeeName . '%');
        });
    }
}

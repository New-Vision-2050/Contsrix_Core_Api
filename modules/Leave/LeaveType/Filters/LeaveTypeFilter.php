<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class LeaveTypeFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($name)
    {
        return $this->where('name', 'LIKE', '%' . $name . '%');
    }

    public function branchId($branchId)
    {
        return $this->whereHas('branches', function ($q) use ($branchId) {
            $q->where('management_hierarchies.id', $branchId);
        });
    }
}

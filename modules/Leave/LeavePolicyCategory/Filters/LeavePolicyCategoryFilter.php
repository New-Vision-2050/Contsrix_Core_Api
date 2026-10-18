<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class LeavePolicyCategoryFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($name)
    {
        return $this->where('name', 'LIKE', '%' . $name . '%');
    }
}

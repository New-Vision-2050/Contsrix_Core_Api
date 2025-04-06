<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AdminRequestFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }
}

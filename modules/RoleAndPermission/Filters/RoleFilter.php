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
}

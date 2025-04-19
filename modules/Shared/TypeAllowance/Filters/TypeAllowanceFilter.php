<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TypeAllowanceFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

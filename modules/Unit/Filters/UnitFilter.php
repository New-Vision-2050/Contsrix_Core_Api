<?php

declare(strict_types=1);

namespace Modules\Unit\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UnitFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TimeUnitFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

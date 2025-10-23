<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class DealDayFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

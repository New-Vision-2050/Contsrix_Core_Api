<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Order\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class OrderFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

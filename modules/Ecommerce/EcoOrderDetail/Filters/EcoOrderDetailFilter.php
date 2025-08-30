<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoOrderDetailFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

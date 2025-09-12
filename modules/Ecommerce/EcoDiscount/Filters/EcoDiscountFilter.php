<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoDiscount\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoDiscountFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

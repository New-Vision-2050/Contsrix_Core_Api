<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WarehousFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

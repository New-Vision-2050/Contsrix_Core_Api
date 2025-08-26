<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoProductFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

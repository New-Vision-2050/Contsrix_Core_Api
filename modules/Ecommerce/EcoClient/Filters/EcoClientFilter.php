<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoClientFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class DashboardFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class BusinessTypeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

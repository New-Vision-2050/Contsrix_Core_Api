<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoReportFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

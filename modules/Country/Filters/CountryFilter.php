<?php

declare(strict_types=1);

namespace Modules\Country\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CountryFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
        public function status($status)
        {
            return $this->where('status', $status);
        }
}
    
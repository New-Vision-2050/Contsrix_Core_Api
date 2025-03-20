<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TimeZoneFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('time_zone', $name);
        }
}

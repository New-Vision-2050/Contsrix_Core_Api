<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PublicHolidayFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', 'LIKE', '%' . $name . '%');
        }
}

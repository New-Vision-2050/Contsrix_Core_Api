<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TypeWorkingHourFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

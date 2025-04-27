<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class NatureWorkFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

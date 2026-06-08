<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class Shared/ProcessFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

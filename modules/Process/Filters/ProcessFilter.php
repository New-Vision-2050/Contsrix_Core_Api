<?php

declare(strict_types=1);

namespace Modules\Process\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProcessFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

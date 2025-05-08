<?php

declare(strict_types=1);

namespace Modules\Program\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProgramFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

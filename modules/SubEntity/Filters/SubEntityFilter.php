<?php

declare(strict_types=1);

namespace Modules\SubEntity\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SubEntityFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

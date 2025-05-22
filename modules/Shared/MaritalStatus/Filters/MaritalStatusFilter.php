<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class MaritalStatusFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

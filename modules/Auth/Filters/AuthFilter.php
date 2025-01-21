<?php

declare(strict_types=1);

namespace Modules\Auth\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AuthFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

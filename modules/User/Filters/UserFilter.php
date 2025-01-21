<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

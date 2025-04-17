<?php

declare(strict_types=1);

namespace Modules\Shared\TypePrivilege\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TypePrivilegeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

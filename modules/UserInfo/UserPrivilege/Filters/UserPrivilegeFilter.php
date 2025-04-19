<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserPrivilegeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

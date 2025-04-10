<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserBankFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

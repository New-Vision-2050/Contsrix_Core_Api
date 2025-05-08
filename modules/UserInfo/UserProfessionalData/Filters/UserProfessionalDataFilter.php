<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserProfessionalDataFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

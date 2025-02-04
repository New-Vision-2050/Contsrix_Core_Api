<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyUserFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyRegistrationFormFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

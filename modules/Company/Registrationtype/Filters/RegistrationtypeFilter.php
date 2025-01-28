<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class RegistrationTypeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

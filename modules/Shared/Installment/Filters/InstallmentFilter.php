<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class InstallmentFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

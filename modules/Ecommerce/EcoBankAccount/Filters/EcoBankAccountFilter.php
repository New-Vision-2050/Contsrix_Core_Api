<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoBankAccountFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

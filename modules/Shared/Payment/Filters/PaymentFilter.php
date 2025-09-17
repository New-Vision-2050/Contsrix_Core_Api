<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PaymentFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

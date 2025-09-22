<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoPaymentFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

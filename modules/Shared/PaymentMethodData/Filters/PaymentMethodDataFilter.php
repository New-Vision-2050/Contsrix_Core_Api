<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PaymentMethodDataFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

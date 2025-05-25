<?php

declare(strict_types=1);

namespace Modules\Shared\BankTypeAccount\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class BankTypeAccountFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

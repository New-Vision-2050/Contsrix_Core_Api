<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoShopAddressFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

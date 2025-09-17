<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoShopFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

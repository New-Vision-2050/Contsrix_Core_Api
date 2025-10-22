<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FlashDealFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

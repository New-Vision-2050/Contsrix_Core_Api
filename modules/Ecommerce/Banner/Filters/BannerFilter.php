<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class BannerFilter extends SearchModelFilter
{
       public $relations = [];

        public function type($type)
        {
            return $this->where('type', $type);
        }
}

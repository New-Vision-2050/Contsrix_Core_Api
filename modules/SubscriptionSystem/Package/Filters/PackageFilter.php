<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PackageFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

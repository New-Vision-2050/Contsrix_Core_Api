<?php

declare(strict_types=1);

namespace Modules\Subscription\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SubscriptionFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

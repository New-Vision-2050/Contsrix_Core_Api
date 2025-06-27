<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ModuleFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class FeatureFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
        public function program($program)
        {
            return $this->where('program_id', $program);
        }
        
}

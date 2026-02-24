<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TermServicesFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

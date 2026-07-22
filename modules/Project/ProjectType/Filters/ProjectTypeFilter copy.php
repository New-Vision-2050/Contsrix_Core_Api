<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProjectTypeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

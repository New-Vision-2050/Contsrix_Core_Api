<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteProjectFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

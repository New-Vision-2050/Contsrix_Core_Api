<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteHomePageFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

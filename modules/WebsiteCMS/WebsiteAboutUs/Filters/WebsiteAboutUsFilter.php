<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteAboutUsFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

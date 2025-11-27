<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteThemeFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteContactInfoFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

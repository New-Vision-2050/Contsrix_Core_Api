<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

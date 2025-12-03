<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteThemeSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

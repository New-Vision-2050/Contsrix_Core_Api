<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class WebsiteHomePageSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

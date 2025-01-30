<?php

declare(strict_types=1);

namespace Modules\Setting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

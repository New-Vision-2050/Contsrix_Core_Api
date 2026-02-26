<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TermServiceSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

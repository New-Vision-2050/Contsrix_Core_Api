<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class TermSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

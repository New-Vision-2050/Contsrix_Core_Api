<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProcedureSettingFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

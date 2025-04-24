<?php

declare(strict_types=1);

namespace Modules\Shared\Language\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class LanguageFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

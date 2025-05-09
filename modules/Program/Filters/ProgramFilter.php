<?php

declare(strict_types=1);

namespace Modules\Program\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProgramFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            $locale = app()->getLocale();
            return $this->whereLike("name->{$locale}",  "%$name%");
        }
}

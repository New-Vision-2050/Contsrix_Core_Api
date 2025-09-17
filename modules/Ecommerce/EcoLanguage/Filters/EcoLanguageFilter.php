<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EcoLanguageFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

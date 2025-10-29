<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class SocialIconFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

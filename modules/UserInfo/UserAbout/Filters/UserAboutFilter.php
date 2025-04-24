<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserAboutFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

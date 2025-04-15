<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UserEducationalCourseFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

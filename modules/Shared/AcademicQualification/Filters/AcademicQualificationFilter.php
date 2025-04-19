<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AcademicQualificationFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

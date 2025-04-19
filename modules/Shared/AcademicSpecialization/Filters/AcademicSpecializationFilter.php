<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AcademicSpecializationFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->where('name', $name);
        }
}

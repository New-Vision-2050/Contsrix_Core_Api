<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProfessionalBodieFilter extends SearchModelFilter
{
       public $relations = [];

        public function name($name)
        {
            return $this->whereHas("translations",function ($query)use($name){
                $query->where("content", "LIKE", "%{$name}%");
            });
        }
}

<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EmployeeTaskTypeFilter extends SearchModelFilter
{
    public $relations = ['user', 'project'];

        public function search($term)
        {
            return $this->where(function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('key', 'like', '%' . $term . '%');
            });
        }

}

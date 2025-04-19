<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyTypeFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'LIKE', "%{$name}%");
    }

    public function countryId($countryId)
    {
        return $this->whereHas('countries', function ($q) use ($countryId) {
            $q->where('country_id', $countryId);
        });
    }
}

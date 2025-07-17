<?php

declare(strict_types=1);

namespace Modules\Country\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CountryFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'LIKE', "%{$name}%");
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function CompanyAccessProgram($company_access_program_id)
    {
        return $this->whereHas('companyAccessProgram', function ($query) use ($company_access_program_id) {
            $query->where('company_access_programs.id', $company_access_program_id);
        });
    }
}

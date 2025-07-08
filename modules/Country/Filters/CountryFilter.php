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

    public function companyAccessProgramId()
    {
        return $this->whereHas('companyAccessProgram', function ($query) {
            $query->where('company_access_programs.id', request('company_access_program_id'));
        });
    }
}

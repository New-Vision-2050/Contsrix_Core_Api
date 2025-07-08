<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyFieldFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'LIKE', "%{$name}%");
    }

    public function companyAccessProgramId()
    {
        return $this->whereHas('companyAccessProgram', function ($query) {
            $query->where('company_access_programs.id', request('company_access_program_id'));
        });
    }
}

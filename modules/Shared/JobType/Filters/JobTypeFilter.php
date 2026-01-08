<?php

declare(strict_types=1);

namespace Modules\Shared\JobType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class JobTypeFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }

    public function company($company)
    {
        return $this->where('company_id', $company);
    }

    public function search($name)
    {
        return $this->whereHas('translations', function ($q) use ($name) {
            $q->where('content', 'like', '%' . $name . '%');
        });
    }
}

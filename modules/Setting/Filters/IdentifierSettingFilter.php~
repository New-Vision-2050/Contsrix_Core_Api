<?php

declare(strict_types=1);

namespace Modules\Setting\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class LoginWayFilter extends SearchModelFilter
{
    public $relations = [];

    public function company_id($id)
    {
        return $this->where('company_id', $id);
    }
}

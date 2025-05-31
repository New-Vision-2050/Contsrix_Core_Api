<?php

declare(strict_types=1);

namespace Modules\AdminRequest\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class AdminRequestFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }
    public function type($type)
    {
        return $this->where('request_type', $type);
    }

    public function company($companyId)
    {
        return $this->where("company_id",$companyId);
    }


}

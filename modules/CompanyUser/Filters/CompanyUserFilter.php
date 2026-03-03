<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyUserFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', $name);
    }

    public function emailOrPhone($value)
    {
        return $this->whereHas('users',function ($q) use ($value) {
                $q->where('phone', 'like', '%' . $value . '%');
                $q->orWhere('email', 'like', '%' . $value . '%');
        });
    }

    public function company($companyId)
    {
        $this->whereHas('companies', function ($q) use ($companyId) {
            $q->where('company_id', '=', $companyId);
        });
    }

    public function status($status)
    {
        $this->whereHas('users.companyUserCompanies', function ($q) use ($status) {
            if ($status == 'active' || $status) {
                $q->where('status', '=', 1);
            } else {
                $q->where('status', '=', 0);
            }
        });
    }

    public function q($value): CompanyUserFilter
    {
        return $this->whereHas('users',function ($q) use ($value) {
            $q->where('name', 'like', '%' . $value . '%');
            $q->orWhere('phone', 'like', '%' . $value . '%');
            $q->orWhere('email', 'like', '%' . $value . '%');
        });
    }
}

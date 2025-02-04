<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->where('name', 'like', '%'.$name .'%');
    }
    public function countryId($countryId)
    {
        return $this->where('country_id', $countryId);
    }
    public function companyTypeId($companyTypeId)
    {
        return $this->where('company_type_id', $companyTypeId);
    }
    public function companyFieldId($companyFieldId)
    {
        return $this->where('company_field_id', $companyFieldId);
    }


    public function search($search)
    {
        return $this->where('name', 'like', '%' . $search . '%')
            ->orWhere('user_name', 'like', '%' . $search . '%')
            ->orWhere('phone', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            ->orWhere('serial_no', 'like', '%' . $search . '%')
            ->orWhere('registration_no', 'like', '%' . $search . '%')
            ->orWhereHas('generalManager', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('country', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('companyType', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('companyField', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('companyRegistrationType', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
    }

}

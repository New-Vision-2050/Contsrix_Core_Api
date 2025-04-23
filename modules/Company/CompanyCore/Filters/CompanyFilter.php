<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class CompanyFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereTranslatable('name',$name);
    }

    public function country($countryId)
    {
        return $this->where('country_id', $countryId);
    }
    public function companyType($companyTypeId)
    {
        return $this->where('company_type_id', $companyTypeId);
    }
    public function companyField($companyFieldId)
    {
        return $this->whereHas('companyField',function($q) use ($companyFieldId) {$q->where('id',$companyFieldId);});
    }


    public function search($search, $filters = [])
    {
        $query = $this;

        $query->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->whereTranslatable('name',$search)
                    ->orWhere('user_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('serial_no', 'like', '%' . $search . '%')
                    ->orWhere('registration_no', 'like', '%' . $search . '%');
            });
        });

        $query->when(isset($filters['general_manager_name']), function ($q) use ($filters) {
            $q->orWhereHas('generalManager', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['general_manager_name'] . '%');
            });
        });

        $query->when(isset($filters['country_name']), function ($q) use ($filters) {
            $q->orWhereHas('country', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['country_name'] . '%');
            });
        });

        $query->when(isset($filters['company_type_name']), function ($q) use ($filters) {
            $q->orWhereHas('companyType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['company_type_name'] . '%');
            });
        });

        $query->when(isset($filters['company_field_name']), function ($q) use ($filters) {
            $q->orWhereHas('companyField', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['company_field_name'] . '%');
            });
        });

        $query->when(isset($filters['company_registration_type_name']), function ($q) use ($filters) {
            $q->orWhereHas('companyRegistrationType', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['company_registration_type_name'] . '%');
            });
        });

        return $query;
    }


}

<?php

declare(strict_types=1);

namespace Modules\User\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Shared\Privilege\Services\PrivilegeCardConfigService;

class UserFilter extends SearchModelFilter
{
    public $relations = [];

    public function userSearch($term)
    {
        return $this->whereHas('user', function ($query) use ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', "%{$term}%")
                ->orWhere('email', 'LIKE', "%{$term}%");
            });
        });
    }
    public function searchText($search)
    {
        return $this->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        });

    }

    public function search($search)
    {
        return $this->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%");
        });

    }

    public function name($search)
    {
        return $this->where('name', 'LIKE', "%{$search}%");

    }
    public function management($managementId)
    {

        return $this->whereHas('userProfessionalData', function ($query) use ($managementId) {
            $query->where('management_id', $managementId);
        });
    }

    public function branch($branchId)
    {
        return $this->whereHas('userProfessionalData', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }

    public function constraint($constraintId)
    {
       return $this->whereHas('professionalData', function ($query) use ($constraintId) {
            $query->where('attendance_constraint_id',$constraintId);
        });
    }

    public function typeAllowanceCode(string $code)
    {
        return $this->whereHas('userPrivileges', function ($query) use ($code) {
            $query->where('company_id', tenant('id'))
                ->where('type_allowance_code', $code)
                ->where(function ($privilegeQuery) {
                    $privilegeQuery
                        ->whereHas('privilege', function ($typeQuery) {
                            $typeQuery->where('type', PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE);
                        })
                        ->orWhereNotNull('medical_insurance_id');
                });
        });
    }
    // public function startDate($date)
    // {
    //     return $this->whereDate('created_at', '>=', $date);
    // }

    // public function endDate($date)
    // {
    //     return $this->whereDate('created_at', '<=', $date);
    // }
}

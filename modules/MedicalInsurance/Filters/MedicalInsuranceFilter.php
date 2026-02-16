<?php

declare(strict_types=1);

namespace Modules\MedicalInsurance\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class MedicalInsuranceFilter extends SearchModelFilter
{
    public $relations = ['employee'];

    public function name($name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    public function policyNumber($policyNumber)
    {
        return $this->where('policy_number', 'like', '%' . $policyNumber . '%');
    }

    public function employeeId($employeeId)
    {
        return $this->where('employee_id', $employeeId);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function endDate($endDate)
    {
        return $this->where('end_date', $endDate);
    }
}

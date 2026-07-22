<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProjectOrderPermitFilter extends SearchModelFilter
{
    public $relations = ['orderPermit', 'contractor'];

    public function name($name)
    {
        return $this->where('name', 'like', '%' . $name . '%');
    }

    public function orderPermitId($orderPermitId)
    {
        return $this->where('order_permit_id', $orderPermitId);
    }


    public function assignedDateFrom($date)
    {
        return $this->where('assigned_date', '>=', $date);
    }


    public function assignedDateTo($date)
    {
        return $this->where('assigned_date', '<=', $date);
    }

    public function stateId($stateId)
    {
        return $this->where('state_id', $stateId);
    }

    public function contractorId($contractorId)
    {
        return $this->where('contractor_id', $contractorId);
    }


// public function orderPermitDepartmentId($departmentId)
// {
//     return $this->whereHas('orderPermit', function ($query) use ($departmentId) {
//         $query->where('order_permit_department_id', (int) $departmentId);
//     });
// }
}

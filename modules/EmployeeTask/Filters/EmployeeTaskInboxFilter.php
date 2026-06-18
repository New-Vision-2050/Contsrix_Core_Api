<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EmployeeTaskInboxFilter extends SearchModelFilter
{
    public function taskId($id)
    {
        return $this->where(function ($q) use ($id) {
            $q->where('id', $id)
              ->orWhere('employee_task_request_id', $id);
        });
    }

    public function taskDate($date)
    {
        return $this->whereDate('task_date', $date);
    }

    public function dateFrom($date)
    {
        return $this->whereDate('task_date', '>=', $date);
    }

    public function dateTo($date)
    {
        return $this->whereDate('task_date', '<=', $date);
    }

    public function type($type)
    {
        return $this;
    }

    public function durationFrom($value)
    {
        return $this->where('duration_hours', '>=',   (float) $value);
    }

    public function durationTo($value)
    {
        return $this->where('duration_hours', '<=', (float) $value);
    }
}

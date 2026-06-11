<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class EmployeeTaskRequestFilter extends SearchModelFilter
{
    public $relations = ['user', 'project'];

    public function status($status)
    {
        if (is_string($status) && str_contains($status, ',')) {
            return $this->whereIn('status', explode(',', $status));
        }

        return $this->where('status', $status);
    }

    public function projectId($projectId)
    {
        return $this->where('project_id', $projectId);
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

    public function search($term)
    {
        return $this->where(function ($query) use ($term) {
            $query->where('title', 'like', '%' . $term . '%')
                  ->orWhere('description', 'like', '%' . $term . '%');
        });
    }
}

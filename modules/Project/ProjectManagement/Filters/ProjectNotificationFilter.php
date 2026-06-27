<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class ProjectNotificationFilter extends SearchModelFilter
{
    public $relations = ['project', 'assignedUser', 'creator'];

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

    public function notificationType($type)
    {
        return $this->where('notification_type', $type);
    }

    public function workType($workType)
    {
        return $this->where('work_type', $workType);
    }

    public function contractorName($name)
    {
        return $this->where('contractor_name', 'like', '%' . $name . '%');
    }

    public function assignedUserId($userId)
    {
        return $this->where('assigned_user_id', $userId);
    }

    public function taskUserId($userId)
    {
        return $this->whereHas('employeeTask', function ($query) use ($userId) {
            $query->where('user_id', $userId);
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

    public function search($term)
    {
        return $this->where(function ($query) use ($term) {
            $query->where('notification_number', 'like', '%' . $term . '%')
                  ->orWhere('contractor_name', 'like', '%' . $term . '%')
                  ->orWhere('work_description', 'like', '%' . $term . '%')
                  ->orWhere('repair_point', 'like', '%' . $term . '%');
        });
    }
}

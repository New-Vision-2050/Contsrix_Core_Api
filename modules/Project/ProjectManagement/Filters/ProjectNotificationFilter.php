<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationEndTaskStatus;
use Modules\Project\ProjectManagement\Models\ProjectNotificationUpdateSiteStatus;

class ProjectNotificationFilter extends SearchModelFilter
{
    protected $drop_id = false;

    public $relations = ['project', 'creator'];

    public function status($status)
    {
        $statuses = is_string($status) && str_contains($status, ',')
            ? explode(',', $status)
            : [$status];

        // Resolve dynamic status keys coming from the status chart / new status labels.
        $updateSiteStatusIds = ProjectNotificationUpdateSiteStatus::query()
            ->whereIn('key', $statuses)
            ->where('is_active', true)
            ->pluck('id', 'key')
            ->toArray();

        $endTaskStatusIds = ProjectNotificationEndTaskStatus::query()
            ->whereIn('key', $statuses)
            ->where('is_active', true)
            ->pluck('id', 'key')
            ->toArray();

        // Fast path: only raw/pseudo statuses with no dynamic keys.
        if (empty($updateSiteStatusIds) && empty($endTaskStatusIds)) {
            if (! array_intersect(['received', 'confirmed_location'], $statuses)) {
                return count($statuses) > 1
                    ? $this->whereIn('project_notifications.status', $statuses)
                    : $this->where('project_notifications.status', $statuses[0]);
            }
        }

        return $this->where(function ($query) use ($statuses, $updateSiteStatusIds, $endTaskStatusIds) {
            foreach ($statuses as $value) {
                $query->orWhere(function ($q) use ($value, $updateSiteStatusIds, $endTaskStatusIds) {
                    if (isset($updateSiteStatusIds[$value])) {
                        $q->where('project_notifications.status', 'in_progress')
                            ->where('project_notifications.update_site_status_id', $updateSiteStatusIds[$value]);

                        return;
                    }

                    if (isset($endTaskStatusIds[$value])) {
                        $q->where('project_notifications.status', 'completed')
                            ->where('project_notifications.end_task_status_id', $endTaskStatusIds[$value]);

                        return;
                    }

                    match ($value) {
                        'received' => $q->where(function ($sq) {
                            $sq->where(function ($ssq) {
                                $ssq->where('project_notifications.status', 'in_progress')
                                    ->whereNull('project_notifications.location_confirmed_at');
                            })->orWhere(function ($ssq) {
                                $ssq->where('project_notifications.status', 'cancelled')
                                    ->whereNotNull('project_notifications.confirmation_receive_date')
                                    ->whereNull('project_notifications.location_confirmed_at');
                            });
                        }),
                        'confirmed_location' => $q->where(function ($sq) {
                            $sq->where(function ($ssq) {
                                $ssq->where('project_notifications.status', 'in_progress')
                                    ->whereNotNull('project_notifications.location_confirmed_at');
                            })->orWhere(function ($ssq) {
                                $ssq->where('project_notifications.status', 'cancelled')
                                    ->whereNotNull('project_notifications.location_confirmed_at');
                            });
                        }),
                        default => $q->where('project_notifications.status', $value),
                    };
                });
            }
        });
    }

    public function projectId($projectId)
    {
        return $this->where('project_notifications.project_id', $projectId);
    }

    public function contractualEngagementKey($code)
    {
        return $this->whereHas('project.contractualEngagement', function ($query) use ($code) {
            $query->where('code', $code);
        });
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

    public function contractorId($contractorId)
    {
        return $this->where('contractor_id', $contractorId);
    }

    public function contractorCategory($category)
    {
        return $this->where('contractor_category', $category);
    }

    public function severity($severity)
    {
        return $this->where('severity', $severity);
    }

    public function assignedUserId($userId)
    {
        return $this->whereJsonContains('assigned_user_ids', $userId);
    }

    public function taskUserId($userId)
    {
        return $this->whereHas('employeeTask', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    public function workflowInboxForUser($userId)
    {
        return $this->whereHas('employeeTask.processes', function ($query) use ($userId) {
            $query->where('processable_type', 'project_notification_task')
                ->where('status', ProcessStatus::InProgress)
                ->whereHas('steps', function ($query) use ($userId) {
                    $query->where('status', ProcessStepStatus::Pending)
                        ->where(function ($query) use ($userId) {
                            $query->where('assigned_user_id', $userId)
                                ->orWhereJsonContains('authorized_user_ids', $userId);
                        });
                });
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

    public function createdByUserId($userId)
    {
        return $this->where('created_by_user_id', $userId);
    }
}

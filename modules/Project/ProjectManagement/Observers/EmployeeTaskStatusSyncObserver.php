<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class EmployeeTaskStatusSyncObserver
{
    private const STATUS_MAP = [
        'pending' => 'pending',
        'approved' => 'approved',
        'rejected' => 'rejected',
        'in_progress' => 'in_progress',
        'paused' => 'in_progress',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];

    public function updated(EmployeeTaskRequest $task): void
    {
        if (!$task->wasChanged('status')) {
            return;
        }

        if (!$task->is_project_notification) {
            return;
        }

        if (!$task->project_notification_id) {
            return;
        }

        $newStatus = self::STATUS_MAP[$task->status] ?? null;

        if ($newStatus === null) {
            return;
        }

        $notification = ProjectNotification::withoutGlobalScopes()
            ->find($task->project_notification_id);

        if (!$notification || $notification->status === $newStatus) {
            return;
        }

        $notification->update(['status' => $newStatus]);
    }
}

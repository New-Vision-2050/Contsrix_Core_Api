<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Observers;

use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectManagement\Repositories\ProjectNotificationRepository;

class ProjectNotificationObserver
{
    public function __construct(
        private readonly ProjectNotificationRepository $repository,
    ) {}

    public function creating(ProjectNotification $notification): void
    {
        if (empty($notification->notification_number)) {
            $notification->notification_number =
                $this->repository->generateNotificationNumber($notification->company_id);
            return;
        }

        // Drafts are allowed to omit a number, but if the client sends one that
        // already exists we must not let the DB unique index throw. Generate a
        // unique number instead so the draft is always created successfully.
        if ($notification->status === 'draft') {
            $exists = ProjectNotification::query()
                ->where('company_id', $notification->company_id)
                ->where('notification_number', $notification->notification_number)
                ->exists();

            if ($exists) {
                $notification->notification_number =
                    $this->repository->generateNotificationNumber($notification->company_id);
            }
        }
    }
}

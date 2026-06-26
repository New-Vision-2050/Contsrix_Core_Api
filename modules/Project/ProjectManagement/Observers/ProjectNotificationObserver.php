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
        }
    }
}

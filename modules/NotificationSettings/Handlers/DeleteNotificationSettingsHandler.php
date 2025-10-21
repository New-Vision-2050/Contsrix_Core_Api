<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Handlers;

use Modules\NotificationSettings\Repositories\NotificationSettingsRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteNotificationSettingsHandler
{
    public function __construct(
        private NotificationSettingsRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteNotificationSettings($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Handlers;

use Modules\NotificationSettings\Commands\UpdateNotificationSettingsCommand;
use Modules\NotificationSettings\Repositories\NotificationSettingsRepository;

class UpdateNotificationSettingsHandler
{
    public function __construct(
        private NotificationSettingsRepository $repository,
    ) {
    }

    public function handle(UpdateNotificationSettingsCommand $updateNotificationSettingsCommand)
    {
        $this->repository->updateNotificationSettings( $updateNotificationSettingsCommand->toArray());
    }
}

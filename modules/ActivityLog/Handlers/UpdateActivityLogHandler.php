<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Handlers;

use Modules\ActivityLog\Commands\UpdateActivityLogCommand;
use Modules\ActivityLog\Repositories\ActivityLogRepository;

class UpdateActivityLogHandler
{
    public function __construct(
        private ActivityLogRepository $repository,
    ) {
    }

    public function handle(UpdateActivityLogCommand $updateActivityLogCommand)
    {
        $this->repository->updateActivityLog($updateActivityLogCommand->getId(), $updateActivityLogCommand->toArray());
    }
}

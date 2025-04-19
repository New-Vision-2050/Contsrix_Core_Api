<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Handlers;

use Modules\ActivityLog\Repositories\ActivityLogRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteActivityLogHandler
{
    public function __construct(
        private ActivityLogRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteActivityLog($id);
    }
}

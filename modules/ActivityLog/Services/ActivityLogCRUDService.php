<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Services;

use Illuminate\Support\Collection;
use Modules\ActivityLog\DTO\CreateActivityLogDTO;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Repositories\ActivityLogRepository;
use Ramsey\Uuid\UuidInterface;

class ActivityLogCRUDService
{
    public function __construct(
        private ActivityLogRepository $repository,
    ) {
    }

    public function create(CreateActivityLogDTO $createActivityLogDTO): ActivityLog
    {
         return $this->repository->createActivityLog($createActivityLogDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ActivityLog
    {
        return $this->repository->getActivityLog(
            id: $id,
        );
    }
}

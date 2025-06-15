<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\ActivityLog\Models\ActivityLog;

/**
 * @property ActivityLog $model
 * @method ActivityLog findOneOrFail($id)
 * @method ActivityLog findOneByOrFail(array $data)
 */
class ActivityLogRepository extends BaseRepository
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    public function getActivityLogList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getActivityLog(UuidInterface $id): ActivityLog
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createActivityLog(array $data): ActivityLog
    {
        return $this->create($data);
    }

    public function updateActivityLog(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteActivityLog(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

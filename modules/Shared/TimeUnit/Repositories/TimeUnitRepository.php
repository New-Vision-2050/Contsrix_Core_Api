<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TimeUnit\Models\TimeUnit;

/**
 * @property TimeUnit $model
 * @method TimeUnit findOneOrFail($id)
 * @method TimeUnit findOneByOrFail(array $data)
 */
class TimeUnitRepository extends BaseRepository
{
    public function __construct(TimeUnit $model)
    {
        parent::__construct($model);
    }

    public function getTimeUnitList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTimeUnit(UuidInterface $id): TimeUnit
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTimeUnit(array $data): TimeUnit
    {
        return $this->create($data);
    }

    public function updateTimeUnit(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTimeUnit(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

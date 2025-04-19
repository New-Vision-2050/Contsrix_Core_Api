<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TimeZone\Models\TimeZone;

/**
 * @property TimeZone $model
 * @method TimeZone findOneOrFail($id)
 * @method TimeZone findOneByOrFail(array $data)
 */
class TimeZoneRepository extends BaseRepository
{
    public function __construct(TimeZone $model)
    {
        parent::__construct($model);
    }

    public function getTimeZoneList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTimeZone(UuidInterface $id): TimeZone
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTimeZone(array $data): TimeZone
    {
        return $this->create($data);
    }

    public function updateTimeZone(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTimeZone(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;

/**
 * @property PublicHoliday $model
 * @method PublicHoliday findOneOrFail($id)
 * @method PublicHoliday findOneByOrFail(array $data)
 */
class PublicHolidayRepository extends BaseRepository
{
    public function __construct(PublicHoliday $model)
    {
        parent::__construct($model);
    }

    public function getPublicHolidayList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPublicHoliday(UuidInterface $id): PublicHoliday
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPublicHoliday(array $data): PublicHoliday
    {
        return $this->create($data);
    }

    public function updatePublicHoliday(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePublicHoliday(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

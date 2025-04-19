<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\Period\Models\Period;

/**
 * @property Period $model
 * @method Period findOneOrFail($id)
 * @method Period findOneByOrFail(array $data)
 */
class PeriodRepository extends BaseRepository
{
    public function __construct(Period $model)
    {
        parent::__construct($model);
    }

    public function getPeriodList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getPeriod(UuidInterface $id): Period
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createPeriod(array $data): Period
    {
        return $this->create($data);
    }

    public function updatePeriod(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deletePeriod(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

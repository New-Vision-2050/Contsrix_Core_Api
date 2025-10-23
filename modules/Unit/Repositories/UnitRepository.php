<?php

declare(strict_types=1);

namespace Modules\Unit\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Unit\Models\Unit;
use App\Traits\HasExport;

/**
 * @property Unit $model
 * @method Unit findOneOrFail($id)
 * @method Unit findOneByOrFail(array $data)
 */
class UnitRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Unit $model)
    {
        parent::__construct($model);
    }

    public function getUnitList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUnit(UuidInterface $id): Unit
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUnit(array $data): Unit
    {
        return $this->create($data);
    }

    public function updateUnit(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUnit(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

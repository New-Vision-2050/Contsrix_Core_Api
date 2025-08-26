<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\Warehous\Models\Warehous;

/**
 * @property Warehous $model
 * @method Warehous findOneOrFail($id)
 * @method Warehous findOneByOrFail(array $data)
 */
class WarehousRepository extends BaseRepository
{
    public function __construct(Warehous $model)
    {
        parent::__construct($model);
    }

    public function getWarehousList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWarehous(UuidInterface $id): Warehous
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWarehous(array $data): Warehous
    {
        return $this->create($data);
    }

    public function updateWarehous(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWarehous(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

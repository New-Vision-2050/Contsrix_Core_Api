<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use App\Traits\HasExport;

/**
 * @property EcoOrder $model
 * @method EcoOrder findOneOrFail($id)
 * @method EcoOrder findOneByOrFail(array $data)
 */
class EcoOrderRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoOrder $model)
    {
        parent::__construct($model);
    }

    public function getEcoOrderList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoOrder(UuidInterface $id): EcoOrder
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoOrder(array $data): EcoOrder
    {
        return $this->create($data);
    }

    public function updateEcoOrder(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoOrder(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use App\Traits\HasExport;

/**
 * @property EcoOrderDetail $model
 * @method EcoOrderDetail findOneOrFail($id)
 * @method EcoOrderDetail findOneByOrFail(array $data)
 */
class EcoOrderDetailRepository extends BaseRepository
{
    use HasExport;

    public function __construct(EcoOrderDetail $model)
    {
        parent::__construct($model);
    }

    public function getEcoOrderDetailList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoOrderDetail(UuidInterface $id): EcoOrderDetail
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoOrderDetail(array $data): EcoOrderDetail
    {
        return $this->create($data);
    }

    public function updateEcoOrderDetail(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoOrderDetail(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

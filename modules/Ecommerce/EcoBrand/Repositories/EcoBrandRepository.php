<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;

/**
 * @property EcoBrand $model
 * @method EcoBrand findOneOrFail($id)
 * @method EcoBrand findOneByOrFail(array $data)
 */
class EcoBrandRepository extends BaseRepository
{
    public function __construct(EcoBrand $model)
    {
        parent::__construct($model);
    }

    public function getEcoBrandList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoBrand(UuidInterface $id): EcoBrand
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoBrand(array $data): EcoBrand
    {
        return $this->create($data);
    }

    public function updateEcoBrand(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoBrand(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

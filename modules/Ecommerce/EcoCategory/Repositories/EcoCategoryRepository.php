<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;

/**
 * @property EcoCategory $model
 * @method EcoCategory findOneOrFail($id)
 * @method EcoCategory findOneByOrFail(array $data)
 */
class EcoCategoryRepository extends BaseRepository
{
    public function __construct(EcoCategory $model)
    {
        parent::__construct($model);
    }

    public function getEcoCategoryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoCategory(UuidInterface $id): EcoCategory
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createEcoCategory(array $data): EcoCategory
    {

        return $this->create($data);
    }

    public function updateEcoCategory(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoCategory(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

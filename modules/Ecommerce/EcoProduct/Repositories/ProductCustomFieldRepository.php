<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\ProductCustomField;

/**
 * @property ProductCustomField $model
 * @method ProductCustomField findOneOrFail($id)
 * @method ProductCustomField findOneByOrFail(array $data)
 */
class ProductCustomFieldRepository extends BaseRepository
{
    public function __construct(ProductCustomField $model)
    {
        parent::__construct($model);
    }

    public function getProductCustomFieldList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProductCustomField(UuidInterface $id): ProductCustomField
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createProductCustomField(array $data): ProductCustomField
    {
        return $this->model::create($data);
    }




    public function updateProductCustomField(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProductCustomField(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

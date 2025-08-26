<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\ProductSEO;

/**
 * @property ProductSEO $model
 * @method ProductSEO findOneOrFail($id)
 * @method ProductSEO findOneByOrFail(array $data)
 */
class ProductSEORepository extends BaseRepository
{
    public function __construct(ProductSEO $model)
    {
        parent::__construct($model);
    }

    public function getProductSEOList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProductSEO(UuidInterface $id): ProductSEO
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createProductSEO(array $data): ProductSEO
    {
        return $this->model::create($data);
    }

    public function updateProductSEO(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProductSEO(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\ProductDetail;

/**
 * @property ProductDetail $model
 * @method ProductDetail findOneOrFail($id)
 * @method ProductDetail findOneByOrFail(array $data)
 */
class ProductDetailRepository extends BaseRepository
{
    public function __construct(ProductDetail $model)
    {
        parent::__construct($model);
    }

    public function getProductDetailList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProductDetail(UuidInterface $id): ProductDetail
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createProductDetail(array $data): ProductDetail
    {
        return $this->model::create($data);
    }

    public function updateProductDetail(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProductDetail(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

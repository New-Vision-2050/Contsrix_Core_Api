<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\ProductTax;

/**
 * @property ProductTax $model
 * @method ProductTax findOneOrFail($id)
 * @method ProductTax findOneByOrFail(array $data)
 */
class ProductTaxRepository extends BaseRepository
{
    public function __construct(ProductTax $model)
    {
        parent::__construct($model);
    }

    public function getProductTaxList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProductTax(UuidInterface $id): ProductTax
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createProductTax(array $data): ProductTax
    {
        return $this->model::create($data);
    }

    public function updateProductTax(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProductTax(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

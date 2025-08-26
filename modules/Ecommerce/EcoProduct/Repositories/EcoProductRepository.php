<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

/**
 * @property EcoProduct $model
 * @method EcoProduct findOneOrFail($id)
 * @method EcoProduct findOneByOrFail(array $data)
 */
class EcoProductRepository extends BaseRepository
{
    public function __construct(EcoProduct $model)
    {
        parent::__construct($model);
    }

    public function getEcoProductList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getEcoProduct(UuidInterface $id): EcoProduct
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

   public function createEcoProduct(array $data): EcoProduct
    {
        $details = $data['details'] ?? null;
        $customFields = $data['custom_fields'] ?? null;
        $seo = $data['seo'] ?? null;

        unset($data['details'], $data['custom_fields'], $data['seo']);

        $ecoProduct =  $this->model::create($data);

        if ($details) {

            $ecoProduct->details()->createMany($details);
        }
        if ($customFields) {
            $ecoProduct->customFields()->createMany($customFields);
        }
        if ($seo) {

            $ecoProduct->seo()->create($seo);
        }

        return $ecoProduct;
    }

    public function updateEcoProduct(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteEcoProduct(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoProduct\DTO\CreateEcoProductDTO;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Ramsey\Uuid\UuidInterface;

class EcoProductCRUDService
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function create(CreateEcoProductDTO $createEcoProductDTO): EcoProduct
    {
         return $this->repository->createEcoProduct($createEcoProductDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoProduct
    {
        return $this->repository->getEcoProduct(
            id: $id,
        );
    }
}

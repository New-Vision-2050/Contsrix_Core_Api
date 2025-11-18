<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Services\Website;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;
use Modules\Ecommerce\EcoProduct\Repositories\EcoProductRepository;
use Ramsey\Uuid\UuidInterface;

class EcoProductWebsiteService
{
    public function __construct(
        private EcoProductRepository $repository,
    ) {
    }

    public function list(
        int $page = 1, 
        int $perPage = 12,
        string $sortBy = 'created_at',
        string $sortDirection = 'desc',
        array $relations = []
    ): array {
        $conditions = ['is_visible' => true];

        return $this->repository->paginated(
            conditions: $conditions,
            page: $page,
            perPage: $perPage,
            orderBy: $sortBy,
            sortBy: $sortDirection,
            relations: $relations,
        );
    }

    public function get(UuidInterface $id, array $relations = []): EcoProduct
    {
        return $this->repository->getVisibleEcoProduct(
            id: $id,
            relations: $relations
        );
    }
}


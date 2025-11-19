<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Services\Website;

use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class EcoCategoryCRUDWebsiteService
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            relations: $relations
        );
    }

    public function get(UuidInterface $id, array $relations = []): EcoCategory
    {
        return $this->repository->getEcoCategory(
            id: $id,
            relations: $relations
        );
    }
}


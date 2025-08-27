<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoCategory\DTO\CreateEcoCategoryDTO;
use Modules\Ecommerce\EcoCategory\Models\EcoCategory;
use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class EcoCategoryCRUDService
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function create(CreateEcoCategoryDTO $createEcoCategoryDTO): EcoCategory
    {
         return $this->repository->createEcoCategory($createEcoCategoryDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10, array $relations = []): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            relations: $relations
        );
    }

    public function get(UuidInterface $id): EcoCategory
    {
        return $this->repository->getEcoCategory(
            id: $id,
        );
    }
}

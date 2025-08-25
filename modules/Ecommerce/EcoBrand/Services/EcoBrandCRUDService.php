<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoBrand\DTO\CreateEcoBrandDTO;
use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use Modules\Ecommerce\EcoBrand\Repositories\EcoBrandRepository;
use Ramsey\Uuid\UuidInterface;

class EcoBrandCRUDService
{
    public function __construct(
        private EcoBrandRepository $repository,
    ) {
    }

    public function create(CreateEcoBrandDTO $createEcoBrandDTO): EcoBrand
    {
         return $this->repository->createEcoBrand($createEcoBrandDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoBrand
    {
        return $this->repository->getEcoBrand(
            id: $id,
        );
    }
}

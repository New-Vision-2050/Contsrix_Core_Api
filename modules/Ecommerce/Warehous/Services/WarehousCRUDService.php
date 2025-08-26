<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Warehous\DTO\CreateWarehousDTO;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Modules\Ecommerce\Warehous\Repositories\WarehousRepository;
use Ramsey\Uuid\UuidInterface;

class WarehousCRUDService
{
    public function __construct(
        private WarehousRepository $repository,
    ) {
    }

    public function create(CreateWarehousDTO $createWarehousDTO): Warehous
    {
         return $this->repository->createWarehous($createWarehousDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Warehous
    {
        return $this->repository->getWarehous(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoOrder\DTO\CreateEcoOrderDTO;
use Modules\Ecommerce\EcoOrder\Models\EcoOrder;
use Modules\Ecommerce\EcoOrder\Repositories\EcoOrderRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoOrderCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoOrderRepository $repository,
    ) {
    }

    public function create(CreateEcoOrderDTO $createEcoOrderDTO): EcoOrder
    {
         return $this->repository->createEcoOrder($createEcoOrderDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoOrder
    {
        return $this->repository->getEcoOrder(
            id: $id,
        );
    }
}

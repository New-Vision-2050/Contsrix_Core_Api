<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoOrderDetail\DTO\CreateEcoOrderDetailDTO;
use Modules\Ecommerce\EcoOrderDetail\Models\EcoOrderDetail;
use Modules\Ecommerce\EcoOrderDetail\Repositories\EcoOrderDetailRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoOrderDetailCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoOrderDetailRepository $repository,
    ) {
    }

    public function create(CreateEcoOrderDetailDTO $createEcoOrderDetailDTO): EcoOrderDetail
    {
         return $this->repository->createEcoOrderDetail($createEcoOrderDetailDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoOrderDetail
    {
        return $this->repository->getEcoOrderDetail(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Unit\Services;

use Illuminate\Support\Collection;
use Modules\Unit\DTO\CreateUnitDTO;
use Modules\Unit\Models\Unit;
use Modules\Unit\Repositories\UnitRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class UnitCRUDService
{
    use HasExportService;

    public function __construct(
        private UnitRepository $repository,
    ) {
    }

    public function create(CreateUnitDTO $createUnitDTO): Unit
    {
         return $this->repository->createUnit($createUnitDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Unit
    {
        return $this->repository->getUnit(
            id: $id,
        );
    }
}

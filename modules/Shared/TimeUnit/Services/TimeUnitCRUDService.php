<?php

declare(strict_types=1);

namespace Modules\Shared\TimeUnit\Services;

use Illuminate\Support\Collection;
use Modules\Shared\TimeUnit\DTO\CreateTimeUnitDTO;
use Modules\Shared\TimeUnit\Models\TimeUnit;
use Modules\Shared\TimeUnit\Repositories\TimeUnitRepository;
use Ramsey\Uuid\UuidInterface;

class TimeUnitCRUDService
{
    public function __construct(
        private TimeUnitRepository $repository,
    ) {
    }

    public function create(CreateTimeUnitDTO $createTimeUnitDTO): TimeUnit
    {
         return $this->repository->createTimeUnit($createTimeUnitDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TimeUnit
    {
        return $this->repository->getTimeUnit(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\Period\Services;

use Illuminate\Support\Collection;
use Modules\Shared\Period\DTO\CreatePeriodDTO;
use Modules\Shared\Period\Models\Period;
use Modules\Shared\Period\Repositories\PeriodRepository;
use Ramsey\Uuid\UuidInterface;

class PeriodCRUDService
{
    public function __construct(
        private PeriodRepository $repository,
    ) {
    }

    public function create(CreatePeriodDTO $createPeriodDTO): Period
    {
         return $this->repository->createPeriod($createPeriodDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Period
    {
        return $this->repository->getPeriod(
            id: $id,
        );
    }
}

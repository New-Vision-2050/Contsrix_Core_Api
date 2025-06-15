<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\Services;

use Illuminate\Support\Collection;
use Modules\Shared\TypeWorkingHour\DTO\CreateTypeWorkingHourDTO;
use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;
use Modules\Shared\TypeWorkingHour\Repositories\TypeWorkingHourRepository;
use Ramsey\Uuid\UuidInterface;

class TypeWorkingHourCRUDService
{
    public function __construct(
        private TypeWorkingHourRepository $repository,
    ) {
    }

    public function create(CreateTypeWorkingHourDTO $createTypeWorkingHourDTO): TypeWorkingHour
    {
         return $this->repository->createTypeWorkingHour($createTypeWorkingHourDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TypeWorkingHour
    {
        return $this->repository->getTypeWorkingHour(
            id: $id,
        );
    }
}

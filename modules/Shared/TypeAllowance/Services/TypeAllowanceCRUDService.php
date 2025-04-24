<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Services;

use Illuminate\Support\Collection;
use Modules\Shared\TypeAllowance\DTO\CreateTypeAllowanceDTO;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Modules\Shared\TypeAllowance\Repositories\TypeAllowanceRepository;
use Ramsey\Uuid\UuidInterface;

class TypeAllowanceCRUDService
{
    public function __construct(
        private TypeAllowanceRepository $repository,
    ) {
    }

    public function create(CreateTypeAllowanceDTO $createTypeAllowanceDTO): TypeAllowance
    {
         return $this->repository->createTypeAllowance($createTypeAllowanceDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TypeAllowance
    {
        return $this->repository->getTypeAllowance(
            id: $id,
        );
    }
}

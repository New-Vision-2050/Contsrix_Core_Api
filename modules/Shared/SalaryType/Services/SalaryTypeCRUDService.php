<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Services;

use Illuminate\Support\Collection;
use Modules\Shared\SalaryType\DTO\CreateSalaryTypeDTO;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\SalaryType\Repositories\SalaryTypeRepository;
use Ramsey\Uuid\UuidInterface;

class SalaryTypeCRUDService
{
    public function __construct(
        private SalaryTypeRepository $repository,
    ) {
    }

    public function create(CreateSalaryTypeDTO $createSalaryTypeDTO): SalaryType
    {
         return $this->repository->createSalaryType($createSalaryTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SalaryType
    {
        return $this->repository->getSalaryType(
            id: $id,
        );
    }
}

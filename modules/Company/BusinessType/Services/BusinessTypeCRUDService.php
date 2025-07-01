<?php

declare(strict_types=1);

namespace Modules\Company\BusinessType\Services;

use Illuminate\Support\Collection;
use Modules\Company\BusinessType\DTO\CreateBusinessTypeDTO;
use Modules\Company\BusinessType\Models\BusinessType;
use Modules\Company\BusinessType\Repositories\BusinessTypeRepository;
use Ramsey\Uuid\UuidInterface;

class BusinessTypeCRUDService
{
    public function __construct(
        private BusinessTypeRepository $repository,
    ) {
    }

    public function create(CreateBusinessTypeDTO $createBusinessTypeDTO): BusinessType
    {
         return $this->repository->createBusinessType($createBusinessTypeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): BusinessType
    {
        return $this->repository->getBusinessType(
            id: $id,
        );
    }
}

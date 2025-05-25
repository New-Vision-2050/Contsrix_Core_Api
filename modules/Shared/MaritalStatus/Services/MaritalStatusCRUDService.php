<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Services;

use Illuminate\Support\Collection;
use Modules\Shared\MaritalStatus\DTO\CreateMaritalStatusDTO;
use Modules\Shared\MaritalStatus\Models\MaritalStatus;
use Modules\Shared\MaritalStatus\Repositories\MaritalStatusRepository;
use Ramsey\Uuid\UuidInterface;

class MaritalStatusCRUDService
{
    public function __construct(
        private MaritalStatusRepository $repository,
    ) {
    }

    public function create(CreateMaritalStatusDTO $createMaritalStatusDTO): MaritalStatus
    {
         return $this->repository->createMaritalStatus($createMaritalStatusDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): MaritalStatus
    {
        return $this->repository->getMaritalStatus(
            id: $id,
        );
    }
}

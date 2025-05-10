<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Illuminate\Support\Collection;
use Modules\SubEntity\DTO\CreateSubEntityDTO;
use Modules\SubEntity\Models\SubEntity;
use Modules\SubEntity\Repositories\SubEntityRepository;
use Ramsey\Uuid\UuidInterface;

class SubEntityCRUDService
{
    public function __construct(
        private SubEntityRepository $repository,
    ) {
    }

    public function create(CreateSubEntityDTO $createSubEntityDTO): SubEntity
    {
        return $this->repository->createSubEntity($createSubEntityDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SubEntity
    {
        return $this->repository->getSubEntity(
            id: $id,
        );
    }

    public function paginatedByProgramId(string $programId, int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getPaginatedByProgramId(
            programId: $programId,
            page: $page,
            perPage: $perPage
        );
    }

    public function paginatedBySuperEntity(string $superEntityId,string $programId, int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getPaginatedBySuperEntity(
            superEntityId: $superEntityId,
            programId: $programId,
            page: $page,
            perPage: $perPage
        );
    }
}

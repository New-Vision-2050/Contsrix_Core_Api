<?php

declare(strict_types=1);

namespace Modules\Shared\University\Services;

use Modules\Shared\University\DTO\CreateUniversityDTO;
use Modules\Shared\University\Models\University;
use Modules\Shared\University\Repositories\UniversityRepository;
use Ramsey\Uuid\UuidInterface;

class UniversityCRUDService
{
    public function __construct(
        private UniversityRepository $repository,
    ) {
    }

    public function create(CreateUniversityDTO $createUniversityDTO): University
    {
         return $this->repository->createUniversity($createUniversityDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
            orderBy: "id",
            sortBy: "asc",
        );
    }

    public function get(UuidInterface $id): University
    {
        return $this->repository->getUniversity(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Services;

use Illuminate\Support\Collection;
use Modules\Shared\NatureWork\DTO\CreateNatureWorkDTO;
use Modules\Shared\NatureWork\Models\NatureWork;
use Modules\Shared\NatureWork\Repositories\NatureWorkRepository;
use Ramsey\Uuid\UuidInterface;

class NatureWorkCRUDService
{
    public function __construct(
        private NatureWorkRepository $repository,
    ) {
    }

    public function create(CreateNatureWorkDTO $createNatureWorkDTO): NatureWork
    {
         return $this->repository->createNatureWork($createNatureWorkDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): NatureWork
    {
        return $this->repository->getNatureWork(
            id: $id,
        );
    }
}

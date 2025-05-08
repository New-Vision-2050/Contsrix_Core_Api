<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\Services;

use Illuminate\Support\Collection;
use Modules\Shared\RightTerminate\DTO\CreateRightTerminateDTO;
use Modules\Shared\RightTerminate\Models\RightTerminate;
use Modules\Shared\RightTerminate\Repositories\RightTerminateRepository;
use Ramsey\Uuid\UuidInterface;

class RightTerminateCRUDService
{
    public function __construct(
        private RightTerminateRepository $repository,
    ) {
    }

    public function create(CreateRightTerminateDTO $createRightTerminateDTO): RightTerminate
    {
         return $this->repository->createRightTerminate($createRightTerminateDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): RightTerminate
    {
        return $this->repository->getRightTerminate(
            id: $id,
        );
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Services;

use Illuminate\Support\Collection;
use Modules\Shared/Process\DTO\CreateShared/ProcessDTO;
use Modules\Shared/Process\Models\Shared/Process;
use Modules\Shared/Process\Repositories\Shared/ProcessRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class Shared/ProcessCRUDService
{
    use HasExportService;

    public function __construct(
        private Shared/ProcessRepository $repository,
    ) {
    }

    public function create(CreateShared/ProcessDTO $createShared/ProcessDTO): Shared/Process
    {
         return $this->repository->createShared/Process($createShared/ProcessDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Shared/Process
    {
        return $this->repository->getShared/Process(
            id: $id,
        );
    }
}

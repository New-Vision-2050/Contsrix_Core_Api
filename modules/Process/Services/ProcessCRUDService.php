<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Illuminate\Support\Collection;
use Modules\Process\DTO\CreateProcessDTO;
use Modules\Process\Models\Process;
use Modules\Process\Repositories\ProcessRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class ProcessCRUDService
{
    use HasExportService;

    public function __construct(
        private ProcessRepository $repository,
    ) {
    }

    public function create(CreateProcessDTO $createProcessDTO): Process
    {
         return $this->repository->createProcess($createProcessDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Process
    {
        return $this->repository->getProcess(
            id: $id,
        );
    }
}

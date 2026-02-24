<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Services;

use Illuminate\Support\Collection;
use Modules\Project\TermServices\DTO\CreateTermServicesDTO;
use Modules\Project\TermServices\Models\TermServices;
use Modules\Project\TermServices\Repositories\TermServicesRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class TermServicesCRUDService
{
    use HasExportService;

    public function __construct(
        private TermServicesRepository $repository,
    ) {
    }

    public function create(CreateTermServicesDTO $createTermServicesDTO): TermServices
    {
         return $this->repository->createTermServices($createTermServicesDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): TermServices
    {
        return $this->repository->getTermServices(
            id: $id,
        );
    }
}

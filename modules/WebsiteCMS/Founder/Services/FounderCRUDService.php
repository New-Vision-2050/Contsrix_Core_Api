<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Services;

use Illuminate\Support\Collection;
use Modules\WebsiteCMS\Founder\DTO\CreateFounderDTO;
use Modules\WebsiteCMS\Founder\Models\Founder;
use Modules\WebsiteCMS\Founder\Repositories\FounderRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class FounderCRUDService
{
    use HasExportService;

    public function __construct(
        private FounderRepository $repository,
    ) {
    }

    public function create(CreateFounderDTO $createFounderDTO): Founder
    {
         return $this->repository->createFounder(
             $createFounderDTO->toArray(),
             $createFounderDTO->getPersonalPhoto()
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Founder
    {
        return $this->repository->getFounder(
            id: $id,
        );
    }
}

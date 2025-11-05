<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Home\DTO\CreateHomeDTO;
use Modules\Ecommerce\Home\Models\Home;
use Modules\Ecommerce\Home\Repositories\HomeRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class HomeCRUDService
{
    use HasExportService;

    public function __construct(
        private HomeRepository $repository,
    ) {
    }

    public function create(CreateHomeDTO $createHomeDTO): Home
    {
         return $this->repository->createHome($createHomeDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Home
    {
        return $this->repository->getHome(
            id: $id,
        );
    }
}

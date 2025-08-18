<?php

declare(strict_types=1);

namespace Modules\Test\Services;

use Illuminate\Support\Collection;
use Modules\Test\DTO\CreateTestDTO;
use Modules\Test\Models\Test;
use Modules\Test\Repositories\TestRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class TestCRUDService
{
    use HasExportService;

    public function __construct(
        private TestRepository $repository,
    ) {
    }

    public function create(CreateTestDTO $createTestDTO): Test
    {
         return $this->repository->createTest($createTestDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): Test
    {
        return $this->repository->getTest(
            id: $id,
        );
    }
}

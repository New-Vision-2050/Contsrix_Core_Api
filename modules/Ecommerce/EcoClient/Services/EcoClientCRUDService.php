<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\EcoClient\DTO\CreateEcoClientDTO;
use Modules\Ecommerce\EcoClient\Models\EcoClient;
use Modules\Ecommerce\EcoClient\Repositories\EcoClientRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class EcoClientCRUDService
{
    use HasExportService;

    public function __construct(
        private EcoClientRepository $repository,
    ) {
    }

    public function create(CreateEcoClientDTO $createEcoClientDTO): EcoClient
    {
         return $this->repository->createEcoClient($createEcoClientDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): EcoClient
    {
        return $this->repository->getEcoClient(
            id: $id,
        );
    }
}

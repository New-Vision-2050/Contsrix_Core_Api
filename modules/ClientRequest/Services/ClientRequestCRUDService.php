<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Support\Collection;
use Modules\ClientRequest\DTO\CreateClientRequestDTO;
use Modules\ClientRequest\Models\ClientRequest;
use Modules\ClientRequest\Repositories\ClientRequestRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class ClientRequestCRUDService
{
    use HasExportService;

    public function __construct(
        private ClientRequestRepository $repository,
    ) {
    }

    public function create(CreateClientRequestDTO $createClientRequestDTO): ClientRequest
    {
         return $this->repository->createClientRequest(
             $createClientRequestDTO->toArray(),
             $createClientRequestDTO->service_ids,
             $createClientRequestDTO->attachments
         );
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): ClientRequest
    {
        return $this->repository->getClientRequest(
            id: $id,
        );
    }
}

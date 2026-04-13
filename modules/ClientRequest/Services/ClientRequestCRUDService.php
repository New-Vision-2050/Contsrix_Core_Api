<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\ClientRequest\DTO\CreateClientRequestDTO;
use Modules\ClientRequest\DTO\UpdateClientRequestDTO;
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
             $createClientRequestDTO->term_setting_ids,
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

    public function getMyRequests(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getMyRequests(
            userId: (string) Auth::id(),
            page: $page,
            perPage: $perPage,
        );
    }

    public function update(UpdateClientRequestDTO $updateClientRequestDTO): ClientRequest
    {
        $uuid = \Ramsey\Uuid\Uuid::fromString($updateClientRequestDTO->id);
        
        $this->repository->updateClientRequest(
            $uuid,
            $updateClientRequestDTO->toArray(),
            $updateClientRequestDTO->service_ids,
            $updateClientRequestDTO->term_setting_ids,
            $updateClientRequestDTO->attachments
        );

        return $this->repository->getClientRequest($uuid);
    }
}

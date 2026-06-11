<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\ClientRequest\DTO\CreateClientRequestDTO;
use Modules\ClientRequest\DTO\UpdateClientRequestDTO;
use Modules\ClientRequest\Events\ClientRequestCreated;
use Modules\ClientRequest\Events\ClientRequestStatusChanged;
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
        $clientRequest = $this->repository->createClientRequest(
            $createClientRequestDTO->toArray(),
            $createClientRequestDTO->service_ids,
            $createClientRequestDTO->term_setting_ids,
            $createClientRequestDTO->attachments
        );

        $clientRequest->load(['company', 'createdByUser', 'receiverEmployees']);
        // foreach ($clientRequest->receiverEmployees as $employee) {
        //     event(new ClientRequestCreated($clientRequest, (string) $employee->id));
        // }

        return $clientRequest;
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated($filters, $page, $perPage);
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

    public function changeStatus(string $id, string $status, ?string $rejectCause = null): ClientRequest
    {
        $uuid = \Ramsey\Uuid\Uuid::fromString($id);

        $data = ['status_client_request' => $status];
        if ($rejectCause !== null) {
            $data['reject_cause'] = $rejectCause;
        }

        $this->repository->updateClientRequest($uuid, $data);

        $clientRequest = $this->repository->getClientRequest($uuid);
        $clientRequest->load(['company', 'createdByUser', 'receiverEmployees']);

        foreach ($clientRequest->receiverEmployees as $employee) {
            event(new ClientRequestStatusChanged($clientRequest, $status, (string) $employee->id));
        }

        return $clientRequest;
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

        $clientRequest = $this->repository->getClientRequest($uuid);
        $clientRequest->load(['company', 'createdByUser', 'receiverEmployees']);
        foreach ($clientRequest->receiverEmployees as $employee) {
            event(new ClientRequestStatusChanged($clientRequest, $updateClientRequestDTO->status_client_request ?? 'updated', (string) $employee->id));
        }

        return $clientRequest;
    }
}

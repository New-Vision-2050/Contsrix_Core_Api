<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Presenters;

use Modules\ClientRequest\Models\ClientRequest;

class ClientRequestMyRequestsPresenter
{
    public function __construct(private ClientRequest $clientRequest)
    {
    }

    public function getData(): array
    {
        return [
            'id' => $this->clientRequest->id,
            'serial_number' => $this->clientRequest->serial_number,
            'client_type' => $this->clientRequest->client_type,
            'client_id' => $this->clientRequest->client_id,
            'owner_company' => $this->clientRequest->company ? [
                'id' => $this->clientRequest->company->id,
                'name' => $this->clientRequest->company->name,
                'serial_number' => $this->clientRequest->company->serial_number ?? null,
            ] : null,
            'status' => $this->clientRequest->status_client_request,
            'client_price_offer_status' => $this->clientRequest->client_price_offer_status,
            'reject_cause' => $this->clientRequest->reject_cause,
            'client' => $this->clientRequest->client ? [
                'id' => $this->clientRequest->client->id,
                'name' => $this->clientRequest->client->name,
            ] : null,
            'sender_user' => $this->clientRequest->relationLoaded('createdByUser') && $this->clientRequest->createdByUser ? [
                'id' => $this->clientRequest->createdByUser->id,
                'name' => $this->clientRequest->createdByUser->name,
            ] : null,
            'receiver_employees' => $this->clientRequest->relationLoaded('receiverEmployees')
                ? $this->clientRequest->receiverEmployees->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])->toArray()
                : [],
            'client_request_type' => $this->clientRequest->relationLoaded('clientRequestType') && $this->clientRequest->clientRequestType
                ? [
                    'id' => $this->clientRequest->clientRequestType->id,
                    'name' => $this->clientRequest->clientRequestType->name,
                ]
                : null,
            'branch' => $this->clientRequest->relationLoaded('branch') && $this->clientRequest->branch
                ? [
                    'id' => $this->clientRequest->branch->id,
                    'name' => $this->clientRequest->branch->name,
                ]
                : null,
            'management' => $this->clientRequest->relationLoaded('management') && $this->clientRequest->management
                ? [
                    'id' => $this->clientRequest->management->id,
                    'name' => $this->clientRequest->management->name,
                ]
                : null,
            'notes' => $this->clientRequest->content,
            'created_at' => $this->clientRequest->created_at?->toISOString(),
            'updated_at' => $this->clientRequest->updated_at?->toISOString(),
        ];
    }

    public static function collection(iterable $items): array
    {
        return collect($items)->map(fn($item) => (new self($item))->getData())->toArray();
    }
}

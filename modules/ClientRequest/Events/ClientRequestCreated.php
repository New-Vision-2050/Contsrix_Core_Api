<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\ClientRequest\Models\ClientRequest;

class ClientRequestCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public ClientRequest $clientRequest,
        public string $receiverUserId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('client-request.' . $this->receiverUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'client-request.created';
    }

    public function broadcastWith(): array
    {
        try {
            return [
                'id' => $this->clientRequest->id,
                'serial_number' => $this->clientRequest->serial_number,
                'client_type' => $this->clientRequest->client_type,
                'status' => $this->clientRequest->status_client_request,
                'owner_company' => $this->clientRequest->company ? [
                    'id' => $this->clientRequest->company->id,
                    'name' => $this->clientRequest->company->name,
                ] : null,
                'sender_user' => $this->clientRequest->createdByUser ? [
                    'id' => $this->clientRequest->createdByUser->id,
                    'name' => $this->clientRequest->createdByUser->name,
                ] : null,
                'notes' => $this->clientRequest->content,
                'created_at' => $this->clientRequest->created_at?->toISOString(),
                'notification_type' => 'client_request_created',
            ];
        } catch (\Exception $e) {
            \Log::error('ClientRequestCreated broadcast error: ' . $e->getMessage());

            return [
                'id' => $this->clientRequest->id,
                'serial_number' => $this->clientRequest->serial_number,
                'status' => $this->clientRequest->status_client_request,
                'notification_type' => 'client_request_created',
            ];
        }
    }
}

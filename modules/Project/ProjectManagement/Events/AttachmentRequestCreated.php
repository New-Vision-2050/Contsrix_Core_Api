<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;

class AttachmentRequestCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AttachmentRequest $attachmentRequest,
        public int $pendingIncomingCount = 0
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('company.' . $this->attachmentRequest->receiver_company_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'attachment-request.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->attachmentRequest->id,
            'serial_number' => $this->attachmentRequest->serial_number,
            'name' => $this->attachmentRequest->name,
            'sender_company_id' => $this->attachmentRequest->sender_company_id,
            'sender_company_name' => $this->attachmentRequest->senderCompany?->name,
            'receiver_company_id' => $this->attachmentRequest->receiver_company_id,
            'receiver_company_name' => $this->attachmentRequest->receiverCompany?->name,
            'project_id' => $this->attachmentRequest->project_id,
            'project_name' => $this->attachmentRequest->project?->name,
            'total_items' => $this->attachmentRequest->items->count(),
            'status' => $this->attachmentRequest->status,
            'created_by' => [
                'id' => $this->attachmentRequest->createdByUser?->id,
                'name' => $this->attachmentRequest->createdByUser?->name,
            ],
            'created_at' => $this->attachmentRequest->created_at?->toISOString(),
            'notification_type' => 'attachment_request',
            'pending_incoming_count' => $this->pendingIncomingCount,
        ];
    }
}

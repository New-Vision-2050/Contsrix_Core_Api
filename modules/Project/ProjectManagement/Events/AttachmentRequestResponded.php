<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;

class AttachmentRequestResponded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AttachmentRequest $attachmentRequest,
        public string $senderUserId,
        public string $action // 'approved', 'declined', 'semi-approved'
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
        return 'attachment-request.responded';
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
            'receiver_company_id' => $this->attachmentRequest->receiver_company_id,
            'receiver_company_name' => $this->attachmentRequest->receiverCompany?->name,
            'project_id' => $this->attachmentRequest->project_id,
            'project_name' => $this->attachmentRequest->project?->name,
            'status' => $this->attachmentRequest->status,
            'action' => $this->action,
            'responded_by' => [
                'id' => $this->attachmentRequest->respondedByUser?->id,
                'name' => $this->attachmentRequest->respondedByUser?->name,
            ],
            'responded_at' => $this->attachmentRequest->responded_at?->toISOString(),
            'notification_type' => 'attachment_request_response',
        ];
    }
}

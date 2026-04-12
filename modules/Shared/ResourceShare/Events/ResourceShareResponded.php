<?php

declare(strict_types=1);

namespace Modules\Shared\ResourceShare\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class ResourceShareResponded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ResourceShare $resourceShare,
        public string $action // 'accepted' or 'rejected'
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->resourceShare->owner_company_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'resource.share-responded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        try {
            return [
                'id' => $this->resourceShare->id,
                'shareable_type' => $this->resourceShare->shareable_type,
                'shareable_id' => $this->resourceShare->shareable_id,
                'owner_company_id' => $this->resourceShare->owner_company_id,
                'shared_with_company_id' => $this->resourceShare->shared_with_company_id,
                'shared_with_company_name' => optional($this->resourceShare->sharedWithCompany)->name ?? 'Unknown',
                'status' => $this->resourceShare->status,
                'action' => $this->action,
                'resource_name' => $this->getResourceName(),
                'responded_by' => [
                    'id' => optional($this->resourceShare->respondedByUser)->id ?? null,
                    'name' => optional($this->resourceShare->respondedByUser)->name ?? 'Unknown',
                ],
                'responded_at' => $this->resourceShare->responded_at?->toISOString(),
                'notification_type' => 'resource_share_response',
            ];
        } catch (\Exception $e) {
            \Log::error('ResourceShareResponded broadcast error: ' . $e->getMessage());
            
            // Return minimal safe data
            return [
                'id' => $this->resourceShare->id,
                'shareable_type' => $this->resourceShare->shareable_type,
                'status' => $this->resourceShare->status,
                'action' => $this->action,
                'notification_type' => 'resource_share_response',
            ];
        }
    }

    /**
     * Get a friendly name for the shared resource
     */
    private function getResourceName(): ?string
    {
        try {
            $shareable = $this->resourceShare->shareable;
            
            if (!$shareable) {
                return 'Shared Resource';
            }

            // Try to get name from common attributes
            return $shareable->name ?? $shareable->title ?? $shareable->serial_number ?? 'Shared Resource';
        } catch (\Exception $e) {
            return 'Shared Resource';
        }
    }
}

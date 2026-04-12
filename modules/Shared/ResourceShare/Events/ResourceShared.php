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

class ResourceShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public ResourceShare $resourceShare
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.' . $this->resourceShare->shared_with_company_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'resource.shared';
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
                'owner_company_name' => optional($this->resourceShare->ownerCompany)->name ?? 'Unknown',
                'shared_with_company_id' => $this->resourceShare->shared_with_company_id,
                'shared_with_company_name' => optional($this->resourceShare->sharedWithCompany)->name ?? 'Unknown',
                'status' => $this->resourceShare->status,
                'resource_name' => $this->getResourceName(),
                'shared_by' => [
                    'id' => optional($this->resourceShare->sharedByUser)->id ?? null,
                    'name' => optional($this->resourceShare->sharedByUser)->name ?? 'Unknown',
                ],
                'notes' => $this->resourceShare->notes,
                'created_at' => $this->resourceShare->created_at?->toISOString(),
                'notification_type' => 'resource_share',
            ];
        } catch (\Exception $e) {
            \Log::error('ResourceShared broadcast error: ' . $e->getMessage());
            
            // Return minimal safe data
            return [
                'id' => $this->resourceShare->id,
                'shareable_type' => $this->resourceShare->shareable_type,
                'status' => $this->resourceShare->status,
                'notification_type' => 'resource_share',
                'created_at' => now()->toISOString(),
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

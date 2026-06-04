<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class InboxCountsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $userId,
        public int $pendingTasks,
        public int $pendingExtensions,
        public int $pendingApprovals,
        public int $total,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('employee-task.inbox-counts.' . $this->userId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'employee-task.inbox-counts';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'pending_tasks' => $this->pendingTasks,
            'pending_extensions' => $this->pendingExtensions,
            'pending_approvals' => $this->pendingApprovals,
            'total' => $this->total,
            'type' => 'inbox_counts',
            'timestamp' => now()->toISOString(),
        ];
    }
}

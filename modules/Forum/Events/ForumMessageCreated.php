<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ForumMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<string, mixed> $message Presented message payload
     */
    public function __construct(
        public string $groupId,
        public array $message,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('forum.group.' . $this->groupId)];
    }

    public function broadcastAs(): string
    {
        return 'forum.message.created';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'message' => $this->message,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ForumMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $groupId,
        public string $messageId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('forum.group.' . $this->groupId)];
    }

    public function broadcastAs(): string
    {
        return 'forum.message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'group_id' => $this->groupId,
            'message_id' => $this->messageId,
        ];
    }
}

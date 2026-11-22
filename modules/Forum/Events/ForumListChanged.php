<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ForumListChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<int, string> $userIds
     * @param string $change published|updated|archived|access_changed
     */
    public function __construct(
        public array $userIds,
        public string $forumId,
        public string $change,
    ) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn (string $userId) => new PrivateChannel('forum.user.' . $userId),
            $this->userIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'forum.list.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'forum_id' => $this->forumId,
            'change' => $this->change,
        ];
    }
}

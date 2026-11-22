<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Lightweight per-user event used to refresh the forum home screen
 * (last message preview and unread badge) for every group member.
 */
class ForumGroupActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<int, string> $userIds
     * @param array<string, mixed> $lastMessage
     */
    public function __construct(
        public array $userIds,
        public string $groupId,
        public array $lastMessage,
        public ?string $forumId = null,
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
        return 'forum.group.activity';
    }

    public function broadcastWith(): array
    {
        return [
            'forum_id' => $this->forumId,
            'group_id' => $this->groupId,
            'last_message' => $this->lastMessage,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a user's access to a group changes (added, removed, role or
 * send permissions changed, group deleted) so the client can refresh.
 */
class ForumMembershipChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<int, string> $userIds
     * @param string $change joined|removed|updated|group_deleted
     */
    public function __construct(
        public array $userIds,
        public string $groupId,
        public string $change,
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
        return 'forum.membership.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'forum_id' => $this->forumId,
            'group_id' => $this->groupId,
            'change' => $this->change,
        ];
    }
}

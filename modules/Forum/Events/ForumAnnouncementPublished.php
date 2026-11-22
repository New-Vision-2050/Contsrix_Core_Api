<?php

declare(strict_types=1);

namespace Modules\Forum\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class ForumAnnouncementPublished implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param array<string, mixed> $announcement Presented announcement payload
     */
    public function __construct(
        public string $companyId,
        public array $announcement,
        public ?string $forumId = null,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('forum.announcements.' . $this->companyId)];
        if ($this->forumId !== null && $this->forumId !== '' && $this->forumId !== $this->companyId) {
            $channels[] = new PrivateChannel('forum.announcements.' . $this->forumId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'forum.announcement.published';
    }

    public function broadcastWith(): array
    {
        return [
            'forum_id' => $this->forumId,
            'announcement' => $this->announcement,
        ];
    }
}

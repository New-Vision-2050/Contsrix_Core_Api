<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumActivityLog;
use Modules\User\Models\User;

class ForumActivityLogger
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     */
    public function log(
        ?string $forumId,
        ?User $actor,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $targetLabel = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        if ($forumId === null || $forumId === '') {
            return;
        }

        ForumActivityLog::query()->create([
            'forum_id' => $forumId,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'before' => $before,
            'after' => $after,
        ]);

        Forum::query()->whereKey($forumId)->update(['updated_at' => now()]);
    }
}

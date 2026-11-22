<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Enums\ForumVisibility;
use Modules\Forum\Models\Forum;

class ForumLocator
{
    /**
     * Forum that receives content created on the original unscoped routes.
     */
    public function defaultForum(): Forum
    {
        $existing = Forum::query()->orderBy('created_at')->first();
        if ($existing !== null) {
            return $existing;
        }

        return Forum::query()->create([
            'name' => __('forum::forum.default_name'),
            'visibility' => ForumVisibility::COMPANY,
            'status' => ForumStatus::ACTIVE,
            'branch_ids' => [],
            'project_ids' => [],
        ]);
    }
}

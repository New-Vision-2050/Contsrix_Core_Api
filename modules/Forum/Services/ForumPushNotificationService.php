<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Support\Str;
use Modules\Forum\Jobs\SendForumPushJob;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumAnnouncement;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumMessage;
use Modules\Forum\Presenters\ForumPresenter;

class ForumPushNotificationService
{
    /**
     * @param array<int, string> $recipientIds
     */
    public function notifyMessage(ForumGroup $group, ForumMessage $message, array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        $senderName = $message->sender?->name ?? '';

        SendForumPushJob::dispatch(
            array_values($recipientIds),
            $group->name,
            trim($senderName . ': ' . Str::limit(ForumPresenter::preview($message), 120), ': '),
            [
                'type' => 'forum_message',
                'forum_id' => (string) ($group->forum_id ?? ''),
                'group_id' => (string) $group->id,
                'message_id' => (string) $message->id,
                'message_type' => $message->type->value,
            ],
        );
    }

    /**
     * @param array<int, string> $recipientIds
     */
    public function notifyAnnouncement(ForumAnnouncement $announcement, array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        SendForumPushJob::dispatch(
            array_values($recipientIds),
            $announcement->title ?: __('forum::forum.new_announcement'),
            Str::limit($announcement->body, 120),
            [
                'type' => 'forum_announcement',
                'forum_id' => (string) ($announcement->forum_id ?? ''),
                'announcement_id' => (string) $announcement->id,
            ],
        );
    }

    /**
     * @param array<int, string> $recipientIds
     */
    public function notifyForumPublished(Forum $forum, array $recipientIds): void
    {
        if ($recipientIds === []) {
            return;
        }

        SendForumPushJob::dispatch(
            array_values($recipientIds),
            $forum->name,
            \Illuminate\Support\Str::limit((string) $forum->description, 120),
            [
                'type' => 'forum_published',
                'forum_id' => (string) $forum->id,
            ],
        );
    }
}

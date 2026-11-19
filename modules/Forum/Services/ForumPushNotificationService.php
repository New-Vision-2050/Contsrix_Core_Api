<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Support\Str;
use Modules\Forum\Jobs\SendForumPushJob;
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
                'announcement_id' => (string) $announcement->id,
            ],
        );
    }
}

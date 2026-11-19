<?php

declare(strict_types=1);

namespace Modules\Forum\Presenters;

use Modules\Forum\Enums\ForumMessageType;
use Modules\Forum\Models\ForumAnnouncement;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Models\ForumMessage;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\User\Models\User;

class ForumPresenter
{
    /**
     * @return array<string, mixed>|null
     */
    public static function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return ['id' => $user->id, 'name' => $user->name];
    }

    public static function preview(ForumMessage $message): string
    {
        return match ($message->type) {
            ForumMessageType::IMAGE => $message->body ?: __('forum::forum.image'),
            ForumMessageType::VOICE => __('forum::forum.voice'),
            ForumMessageType::FILE => $message->body ?: __('forum::forum.file'),
            default => (string) $message->body,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function message(ForumMessage $message): array
    {
        return [
            'id' => $message->id,
            'group_id' => $message->group_id,
            'type' => $message->type->value,
            'body' => $message->body,
            'duration_seconds' => $message->duration_seconds,
            'sender' => self::user($message->sender),
            'attachments' => MediaPresenter::collection($message->getMedia(ForumMessage::ATTACHMENT_COLLECTION)),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function lastMessage(?ForumMessage $message): ?array
    {
        if ($message === null) {
            return null;
        }

        return [
            'id' => $message->id,
            'type' => $message->type->value,
            'preview' => self::preview($message),
            'sender' => self::user($message->sender),
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function announcement(ForumAnnouncement $announcement, ?bool $isRead = null): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'body' => $announcement->body,
            'author' => self::user($announcement->author),
            'attachments' => MediaPresenter::collection($announcement->getMedia(ForumAnnouncement::ATTACHMENT_COLLECTION)),
            'is_read' => $isRead,
            'created_at' => $announcement->created_at?->toISOString(),
        ];
    }

    /**
     * @param array<string, mixed> $access
     * @return array<string, mixed>
     */
    public static function group(ForumGroup $group, array $access, int $unreadCount = 0): array
    {
        $canRead = $access['can_read'];

        return [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'icon_url' => $group->getFirstMediaUrl(ForumGroup::ICON_COLLECTION) ?: null,
            'members_count' => (int) ($group->members_count ?? 0),
            'admins_count' => (int) ($group->admins_count ?? 0),
            'send_permission' => $group->send_permission->value,
            'allow_images' => $group->allow_images,
            'allow_voice' => $group->allow_voice,
            'allow_files' => $group->allow_files,
            'is_locked' => ! $canRead,
            'unread_count' => $canRead ? $unreadCount : 0,
            'last_message' => $canRead ? self::lastMessage($group->lastMessage) : null,
            'access' => $access,
            'created_at' => $group->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function member(ForumGroupMember $member): array
    {
        return [
            'user_id' => $member->user_id,
            'name' => $member->user?->name,
            'role' => $member->role->value,
            'can_send' => $member->can_send,
        ];
    }
}

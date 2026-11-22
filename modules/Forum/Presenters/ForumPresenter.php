<?php

declare(strict_types=1);

namespace Modules\Forum\Presenters;

use Modules\Forum\Enums\ForumMessageType;
use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Enums\ForumVisibility;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumActivityLog;
use Modules\Forum\Models\ForumAnnouncement;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Models\ForumMessage;
use Modules\Forum\Models\ForumReport;
use Modules\Forum\Services\ForumAccessService;
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
            'forum_id' => $message->group?->forum_id,
            'attachments' => self::attachments($message->getMedia(ForumMessage::ATTACHMENT_COLLECTION)),
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
            'forum_id' => $announcement->forum_id,
            'title' => $announcement->title,
            'body' => $announcement->body,
            'status' => $announcement->status ?? 'published',
            'notify' => (bool) $announcement->notify,
            'author' => self::user($announcement->author),
            'attachments' => self::attachments($announcement->getMedia(ForumAnnouncement::ATTACHMENT_COLLECTION)),
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
            'forum_id' => $group->forum_id,
            'name' => $group->name,
            'description' => $group->description,
            'icon_url' => $group->getFirstMediaUrl(ForumGroup::ICON_COLLECTION) ?: null,
            'category' => $group->category,
            'show_name_in_chat' => (bool) ($group->show_name_in_chat ?? true),
            'status' => $group->status instanceof ForumStatus ? $group->status->value : ($group->status ?? 'active'),
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
            'role' => $member->role->value,
            'can_send' => $member->can_send,
        ] + self::person($member->user);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forum(Forum $forum, User $user, ForumAccessService $access): array
    {
        $role = null;
        if ($access->isForumAdmin($user)) {
            $role = 'owner';
        } elseif ($access->adminRecord($user, (string) $forum->id) !== null) {
            $role = 'admin';
        }

        return [
            'id' => $forum->id,
            'name' => $forum->name,
            'description' => $forum->description,
            'details' => $forum->details,
            'logo_url' => $forum->getFirstMediaUrl(Forum::LOGO_COLLECTION) ?: null,
            'visibility' => $forum->visibility instanceof ForumVisibility ? $forum->visibility->value : $forum->visibility,
            'branch_ids' => $forum->branch_ids ?? [],
            'project_ids' => $forum->project_ids ?? [],
            'status' => $forum->status instanceof ForumStatus ? $forum->status->value : $forum->status,
            'groups_count' => (int) ($forum->groups_count ?? 0),
            'members_count' => (int) ($forum->members_count ?? 0),
            'admins_count' => (int) ($forum->admins_count ?? 0),
            'open_reports_count' => (int) ($forum->open_reports_count ?? 0),
            'last_activity_at' => $forum->updated_at?->toISOString(),
            'archived_at' => $forum->archived_at?->toISOString(),
            'my_role' => $role,
            'created_at' => $forum->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function person(?User $user): array
    {
        $professional = $user?->professionalData;
        $jobTitle = $professional?->jobTitle?->name;

        return [
            'user_id' => $user?->id,
            'id' => $user?->id,
            'name' => $user?->name,
            'email' => $user?->email,
            'employee_number' => $professional?->job_code,
            'job_title' => self::text($jobTitle),
            'branch_name' => self::text($professional?->branch?->name ?? $user?->managementHierarchy?->name),
            'avatar_url' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function admin(\Modules\Forum\Models\ForumAdmin $admin): array
    {
        return [
            'user_id' => $admin->user_id,
            'can_manage_groups' => $admin->can_manage_groups,
            'can_publish_announcements' => $admin->can_publish_announcements,
        ] + self::person($admin->user);
    }

    /**
     * @return array<string, mixed>
     */
    public static function report(ForumReport $report): array
    {
        $message = $report->message;

        return [
            'id' => $report->id,
            'group_id' => $report->group_id,
            'group_name' => $report->group?->name,
            'message_id' => $report->message_id,
            'message' => $message ? self::message($message) : null,
            'reason' => $report->reason,
            'reporter' => self::user($report->reporter),
            'status' => $report->status instanceof \Modules\Forum\Enums\ForumReportStatus ? $report->status->value : $report->status,
            'resolution_note' => $report->resolution_note,
            'created_at' => $report->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function activity(ForumActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'action' => $log->action,
            'action_label' => __('forum::forum.activity.' . str_replace('.', '_', $log->action)),
            'actor' => self::user($log->actor),
            'target_type' => $log->target_type,
            'target_id' => $log->target_id,
            'target_label' => $log->target_label,
            'before' => $log->before,
            'after' => $log->after,
            'created_at' => $log->created_at?->toISOString(),
        ];
    }

    /**
     * @param iterable<int, mixed> $media
     * @return array<int, array<string, mixed>>
     */
    public static function attachments(iterable $media): array
    {
        return array_map(function (array $file) {
            $mime = (string) ($file['mime_type'] ?? '');
            $file['type'] = str_starts_with($mime, 'image/') ? 'image' : (str_starts_with($mime, 'audio/') ? 'voice' : 'file');

            return $file;
        }, MediaPresenter::collection($media));
    }

    public static function text(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            $picked = $value[app()->getLocale()] ?? $value['ar'] ?? $value['en'] ?? null;
            if (is_string($picked) && $picked !== '') {
                return $picked;
            }
            foreach ($value as $item) {
                if (is_string($item) && $item !== '') {
                    return $item;
                }
            }
        }

        return null;
    }
}

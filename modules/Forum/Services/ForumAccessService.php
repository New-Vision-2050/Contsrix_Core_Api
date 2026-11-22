<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumMessageType;
use Modules\Forum\Enums\ForumSendPermission;
use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Enums\ForumVisibility;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumAdmin;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Models\ForumMember;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;

class ForumAccessService
{
    /**
     * True when the user's role includes any forum management permission.
     * Mobile reads this as is_forum_admin. It is not a role-name check.
     */
    public function isForumAdmin(User $user): bool
    {
        foreach ([
            Permission::FORUM_CREATE(),
            Permission::FORUM_UPDATE(),
            Permission::FORUM_ARCHIVE(),
            Permission::FORUM_GROUP_CREATE(),
            Permission::FORUM_GROUP_UPDATE(),
            Permission::FORUM_ANNOUNCEMENT_CREATE(),
            Permission::FORUM_MEMBER_UPDATE(),
            Permission::FORUM_ADMIN_UPDATE(),
            Permission::FORUM_REPORT_UPDATE(),
        ] as $permission) {
            if ($this->allows($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function allows(User $user, string $permission): bool
    {
        return $user->can($permission);
    }

    public function assertAllows(User $user, string $permission): void
    {
        abort_unless($this->allows($user, $permission), 403, __('forum::forum.only_admins'));
    }

    public function assertForumAdmin(User $user): void
    {
        abort_unless($this->isForumAdmin($user), 403, __('forum::forum.only_admins'));
    }

    /**
     * Buttons the mobile app may show. Each flag is one role permission.
     *
     * @return array<string, bool>
     */
    public function capabilities(User $user): array
    {
        return [
            'can_create' => $this->allows($user, Permission::FORUM_CREATE()),
            'can_update' => $this->allows($user, Permission::FORUM_UPDATE()),
            'can_archive' => $this->allows($user, Permission::FORUM_ARCHIVE()),
            'can_restore' => $this->allows($user, Permission::FORUM_RESTORE()),
            'can_manage_members' => $this->allows($user, Permission::FORUM_MEMBER_UPDATE()),
            'can_manage_admins' => $this->allows($user, Permission::FORUM_ADMIN_UPDATE()),
            'can_create_groups' => $this->allows($user, Permission::FORUM_GROUP_CREATE()),
            'can_update_groups' => $this->allows($user, Permission::FORUM_GROUP_UPDATE()),
            'can_delete_groups' => $this->allows($user, Permission::FORUM_GROUP_DELETE()),
            'can_archive_groups' => $this->allows($user, Permission::FORUM_GROUP_ARCHIVE()),
            'can_restore_groups' => $this->allows($user, Permission::FORUM_GROUP_RESTORE()),
            'can_publish_announcements' => $this->allows($user, Permission::FORUM_ANNOUNCEMENT_CREATE()),
            'can_delete_announcements' => $this->allows($user, Permission::FORUM_ANNOUNCEMENT_DELETE()),
            'can_review_reports' => $this->allows($user, Permission::FORUM_REPORT_UPDATE()),
        ];
    }

    public function canPickEmployees(User $user): bool
    {
        return $this->allows($user, Permission::FORUM_CREATE())
            || $this->allows($user, Permission::FORUM_GROUP_CREATE())
            || $this->allows($user, Permission::FORUM_MEMBER_UPDATE())
            || $this->allows($user, Permission::FORUM_ADMIN_UPDATE());
    }

    public function adminRecord(User $user, string $forumId): ?ForumAdmin
    {
        if ($forumId === '') {
            return null;
        }

        return ForumAdmin::query()
            ->where('forum_id', $forumId)
            ->where('user_id', $user->id)
            ->first();
    }

    public function isForumManager(User $user, string $forumId): bool
    {
        if ($forumId === '') {
            return false;
        }

        return $this->isForumAdmin($user);
    }

    public function canManageGroups(User $user, Forum $forum): bool
    {
        return $this->allows($user, Permission::FORUM_GROUP_CREATE())
            || $this->allows($user, Permission::FORUM_GROUP_UPDATE());
    }

    public function canPublishAnnouncements(User $user, Forum $forum): bool
    {
        return $this->allows($user, Permission::FORUM_ANNOUNCEMENT_CREATE())
            || $this->allows($user, Permission::FORUM_ANNOUNCEMENT_UPDATE());
    }

    public function canReviewReports(User $user): bool
    {
        return $this->allows($user, Permission::FORUM_REPORT_LIST())
            || $this->allows($user, Permission::FORUM_REPORT_UPDATE());
    }

    public function assertCanManageGroups(User $user, Forum $forum): void
    {
        abort_unless($this->canManageGroups($user, $forum), 403, __('forum::forum.only_admins'));
    }

    public function assertCanPublish(User $user, Forum $forum): void
    {
        abort_unless($this->canPublishAnnouncements($user, $forum), 403, __('forum::forum.only_admins'));
    }

    public function assertCanReview(User $user, Forum $forum): void
    {
        abort_unless($this->canReviewReports($user), 403, __('forum::forum.only_admins'));
    }

    public function canSeeForum(User $user, Forum $forum): bool
    {
        if ($forum->status !== ForumStatus::ACTIVE) {
            return false;
        }

        if ($this->isForumManager($user, (string) $forum->id)) {
            return true;
        }

        if (ForumMember::query()->where('forum_id', $forum->id)->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($forum->visibility === ForumVisibility::COMPANY) {
            return true;
        }

        $branchIds = array_map('strval', $forum->branch_ids ?? []);
        $candidates = array_filter([
            (string) ($user->professionalData?->branch_id ?? ''),
            (string) ($user->professionalData?->management_id ?? ''),
            (string) ($user->professionalData?->department_id ?? ''),
            (string) ($user->management_hierarchy_id ?? ''),
        ]);

        if ($branchIds !== [] && array_intersect($candidates, $branchIds) !== []) {
            return true;
        }

        $projectIds = array_map('strval', $forum->project_ids ?? []);
        if ($projectIds === []) {
            return false;
        }

        return $user->projects()->whereIn('projects.id', $projectIds)->exists();
    }

    public function assertCanOpen(User $user, Forum $forum): void
    {
        abort_unless(
            $this->canSeeForum($user, $forum) || $this->isForumManager($user, (string) $forum->id),
            403,
            __('forum::forum.forum_forbidden'),
        );
    }

    public function membership(ForumGroup $group, User $user): ?ForumGroupMember
    {
        return ForumGroupMember::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function canView(ForumGroup $group, User $user, ?ForumGroupMember $member = null): bool
    {
        return $this->isForumAdmin($user)
            || $member !== null
            || $this->isForumManager($user, (string) ($group->forum_id ?? ''));
    }

    public function canSend(ForumGroup $group, User $user, ?ForumGroupMember $member = null): bool
    {
        if ($this->isForumAdmin($user) || $this->isForumManager($user, (string) ($group->forum_id ?? ''))) {
            return true;
        }

        if ($member === null) {
            return false;
        }

        if ($member->role === ForumMemberRole::ADMIN) {
            return true;
        }

        return match ($group->send_permission) {
            ForumSendPermission::ALL_MEMBERS => true,
            ForumSendPermission::SPECIFIC_MEMBERS => $member->can_send,
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedTypes(ForumGroup $group, User $user, ?ForumGroupMember $member = null): array
    {
        if (! $this->canSend($group, $user, $member)) {
            return [];
        }

        $types = [ForumMessageType::TEXT->value];

        if ($group->allow_images) {
            $types[] = ForumMessageType::IMAGE->value;
        }
        if ($group->allow_voice) {
            $types[] = ForumMessageType::VOICE->value;
        }
        if ($group->allow_files) {
            $types[] = ForumMessageType::FILE->value;
        }

        return $types;
    }

    /**
     * @return array<string, mixed>
     */
    public function accessFor(ForumGroup $group, User $user, ?ForumGroupMember $member = null): array
    {
        return [
            'group_id' => $group->id,
            'is_member' => $member !== null,
            'my_role' => $member?->role->value,
            'can_read' => $this->canView($group, $user, $member),
            'can_send' => $this->canSend($group, $user, $member),
            'allowed_message_types' => $this->allowedTypes($group, $user, $member),
            'can_manage' => $this->allows($user, Permission::FORUM_GROUP_UPDATE()),
        ];
    }

    public function assertCanView(ForumGroup $group, User $user): ?ForumGroupMember
    {
        $member = $this->membership($group, $user);
        abort_unless($this->canView($group, $user, $member), 403, __('forum::forum.not_member'));

        return $member;
    }
}

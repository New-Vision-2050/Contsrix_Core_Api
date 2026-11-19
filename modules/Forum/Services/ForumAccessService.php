<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumMessageType;
use Modules\Forum\Enums\ForumSendPermission;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\User\Models\User;

class ForumAccessService
{
    public function isForumAdmin(User $user): bool
    {
        return (bool) $user->is_owner || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function assertForumAdmin(User $user): void
    {
        abort_unless($this->isForumAdmin($user), 403, __('forum::forum.only_admins'));
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
        return $this->isForumAdmin($user) || $member !== null;
    }

    public function canSend(ForumGroup $group, User $user, ?ForumGroupMember $member = null): bool
    {
        if ($this->isForumAdmin($user)) {
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
            'can_manage' => $this->isForumAdmin($user),
        ];
    }

    public function assertCanView(ForumGroup $group, User $user): ?ForumGroupMember
    {
        $member = $this->membership($group, $user);
        abort_unless($this->canView($group, $user, $member), 403, __('forum::forum.not_member'));

        return $member;
    }
}

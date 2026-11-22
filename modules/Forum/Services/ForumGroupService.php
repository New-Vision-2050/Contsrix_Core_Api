<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumSendPermission;
use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Events\ForumMembershipChanged;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Models\ForumGroupMember;
use Modules\User\Models\User;

class ForumGroupService
{
    public function __construct(
        private ForumAccessService $access,
        private ForumLocator $forums,
        private ForumActivityLogger $activity,
    ) {}

    public function query(): Builder
    {
        return ForumGroup::query()
            ->where('company_id', tenant('id'))
            ->withCount([
                'members',
                'members as admins_count' => fn (Builder $q) => $q->where('role', ForumMemberRole::ADMIN->value),
            ]);
    }

    public function find(string $id, ?string $forumId = null): ForumGroup
    {
        return $this->query()
            ->with('lastMessage.sender')
            ->when($forumId, fn (Builder $q) => $q->where('forum_id', $forumId))
            ->findOrFail($id);
    }

    /**
     * @return Collection<int, array{group: ForumGroup, access: array<string, mixed>, unread: int}>
     */
    public function listFor(
        User $user,
        string $scope = 'all',
        ?string $search = null,
        ?string $forumId = null,
        ?string $status = null,
        bool $activeOnly = false,
        ?string $sendPermission = null,
    ): Collection {
        $query = $this->query()->with('lastMessage.sender');

        if ($forumId !== null && $forumId !== '') {
            $query->where('forum_id', $forumId);
        }

        if ($activeOnly) {
            $query->where('status', ForumStatus::ACTIVE->value);
        } elseif (in_array($status, [ForumStatus::ACTIVE->value, ForumStatus::ARCHIVED->value], true)) {
            $query->where('status', $status);
        }

        if (in_array($sendPermission, array_column(ForumSendPermission::cases(), 'value'), true)) {
            $query->where('send_permission', $sendPermission);
        }

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $groups = $query->get();

        $memberships = ForumGroupMember::query()
            ->where('user_id', $user->id)
            ->whereIn('group_id', $groups->pluck('id'))
            ->get()
            ->keyBy('group_id');

        $unread = $this->unreadCounts((string) $user->id);

        $rows = $groups->map(fn (ForumGroup $group) => [
            'group' => $group,
            'access' => $this->access->accessFor($group, $user, $memberships->get($group->id)),
            'unread' => $unread[$group->id] ?? 0,
        ]);

        if ($scope === 'mine') {
            $rows = $rows->filter(fn (array $row) => $row['access']['is_member']);
        } elseif ($scope === 'others') {
            $rows = $rows->reject(fn (array $row) => $row['access']['is_member']);
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['group']->lastMessage?->created_at?->timestamp ?? $row['group']->created_at?->timestamp ?? 0)
            ->values();
    }

    /**
     * @return array<string, int> group_id => unread messages for the user
     */
    public function unreadCounts(string $userId): array
    {
        return DB::table('forum_messages as m')
            ->join('forum_group_members as gm', function ($join) use ($userId) {
                $join->on('gm.group_id', '=', 'm.group_id')->where('gm.user_id', '=', $userId);
            })
            ->where('m.sender_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('gm.last_read_at')->orWhereColumn('m.created_at', '>', 'gm.last_read_at');
            })
            ->groupBy('m.group_id')
            ->selectRaw('m.group_id, count(*) as total')
            ->pluck('total', 'group_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, array<string, mixed>> $members
     */
    public function create(array $data, User $creator, ?UploadedFile $icon, array $members = []): ForumGroup
    {
        return DB::transaction(function () use ($data, $creator, $icon, $members) {
            if (empty($data['forum_id'])) {
                $data['forum_id'] = $this->forums->defaultForum()->id;
            }

            $group = ForumGroup::query()->create($data + ['created_by' => $creator->id]);

            if ($icon !== null) {
                $group->addMedia($icon)->toMediaCollection(ForumGroup::ICON_COLLECTION);
            }

            if ($members !== []) {
                $this->syncMembers($group, $members);
            }

            $this->activity->log(
                (string) $group->forum_id,
                $creator,
                'group.created',
                'group',
                (string) $group->id,
                $group->name,
            );

            return $group;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(ForumGroup $group, array $data, ?UploadedFile $icon, ?User $actor = null): ForumGroup
    {
        $before = $group->only(['name', 'description', 'category', 'show_name_in_chat']);
        $group->update($data);

        if ($icon !== null) {
            $group->addMedia($icon)->toMediaCollection(ForumGroup::ICON_COLLECTION);
        }

        $this->activity->log(
            (string) $group->forum_id,
            $actor,
            'group.updated',
            'group',
            (string) $group->id,
            $group->name,
            $before,
            $group->only(['name', 'description', 'category', 'show_name_in_chat']),
        );

        return $group;
    }

    public function setStatus(ForumGroup $group, ForumStatus $status, ?User $actor = null): ForumGroup
    {
        $before = ['status' => $group->status instanceof ForumStatus ? $group->status->value : $group->status];
        $group->update(['status' => $status->value]);

        $this->activity->log(
            (string) $group->forum_id,
            $actor,
            $status === ForumStatus::ARCHIVED ? 'group.archived' : 'group.updated',
            'group',
            (string) $group->id,
            $group->name,
            $before,
            ['status' => $status->value],
        );

        return $group;
    }

    public function delete(ForumGroup $group): void
    {
        $memberIds = $group->members()->pluck('user_id')->all();
        $groupId = $group->id;
        $forumId = (string) $group->forum_id;

        DB::transaction(function () use ($group) {
            $group->messages()->each(fn ($message) => $message->clearMediaCollection());
            $group->delete();
        });

        $this->dispatchMembership($memberIds, $groupId, 'group_deleted', $forumId);
    }

    /**
     * Replace the group's member list.
     *
     * @param array<int, array{user_id: string, role?: string, can_send?: bool}> $members
     */
    public function syncMembers(ForumGroup $group, array $members, ?User $actor = null): void
    {
        $incoming = collect($members)->keyBy('user_id');

        $validIds = User::query()
            ->where('company_id', tenant('id'))
            ->whereIn('id', $incoming->keys())
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        $existing = $group->members()->get()->keyBy('user_id');
        $removed = $existing->keys()->diff($validIds)->values()->all();
        $joined = [];

        DB::transaction(function () use ($group, $incoming, $validIds, $existing, $removed, &$joined) {
            if ($removed !== []) {
                $group->members()->whereIn('user_id', $removed)->delete();
            }

            foreach ($validIds as $userId) {
                $row = $incoming[$userId];
                $attributes = [
                    'role' => $row['role'] ?? ForumMemberRole::MEMBER->value,
                    'can_send' => (bool) ($row['can_send'] ?? false),
                ];

                if ($existing->has($userId)) {
                    $existing[$userId]->update($attributes);
                    continue;
                }

                $group->members()->create($attributes + ['user_id' => $userId, 'last_read_at' => now()]);
                $joined[] = $userId;
            }
        });

        $forumId = (string) $group->forum_id;
        $this->activity->log($forumId, $actor, 'group.members_updated', 'group', (string) $group->id, $group->name);
        $this->dispatchMembership($removed, $group->id, 'removed', $forumId);
        $this->dispatchMembership($joined, $group->id, 'joined', $forumId);
        $this->dispatchMembership(
            $validIds->diff($joined)->values()->all(),
            $group->id,
            'updated',
            $forumId,
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<int, string> $allowedUserIds Members allowed to send (specific_members mode)
     */
    public function updateSendSettings(ForumGroup $group, array $settings, array $allowedUserIds, ?User $actor = null): ForumGroup
    {
        $before = [
            'send_permission' => $group->send_permission instanceof ForumSendPermission ? $group->send_permission->value : $group->send_permission,
            'allow_images' => $group->allow_images,
            'allow_voice' => $group->allow_voice,
            'allow_files' => $group->allow_files,
        ];

        DB::transaction(function () use ($group, $settings, $allowedUserIds) {
            $group->update($settings);

            if ($group->send_permission === ForumSendPermission::SPECIFIC_MEMBERS) {
                $group->members()->update(['can_send' => false]);
                $group->members()->whereIn('user_id', $allowedUserIds)->update(['can_send' => true]);
            }
        });

        $group->refresh();
        $this->activity->log(
            (string) $group->forum_id,
            $actor,
            'group.send_settings_updated',
            'group',
            (string) $group->id,
            $group->name,
            $before,
            [
                'send_permission' => $group->send_permission instanceof ForumSendPermission ? $group->send_permission->value : $group->send_permission,
                'allow_images' => $group->allow_images,
                'allow_voice' => $group->allow_voice,
                'allow_files' => $group->allow_files,
            ],
        );
        $this->dispatchMembership($group->members()->pluck('user_id')->all(), $group->id, 'updated', (string) $group->forum_id);

        return $group;
    }

    public function markRead(ForumGroup $group, User $user): void
    {
        ForumGroupMember::query()
            ->where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * @param array<int, string> $userIds
     */
    private function dispatchMembership(array $userIds, string $groupId, string $change, ?string $forumId = null): void
    {
        if ($userIds === []) {
            return;
        }

        event(new ForumMembershipChanged(array_values($userIds), $groupId, $change, $forumId));
    }
}

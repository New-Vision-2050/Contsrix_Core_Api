<?php

declare(strict_types=1);

namespace Modules\Forum\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Forum\Enums\ForumMemberRole;
use Modules\Forum\Enums\ForumStatus;
use Modules\Forum\Enums\ForumVisibility;
use Modules\Forum\Events\ForumListChanged;
use Modules\Forum\Events\ForumMembershipChanged;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumGroupMember;
use Modules\Forum\Models\ForumMember;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\User\Models\User;

class ForumCatalogService
{
    public function __construct(
        private ForumAccessService $access,
        private ForumActivityLogger $activity,
        private ForumAudience $audience,
        private ForumPushNotificationService $push,
    ) {}

    public function find(string $id): Forum
    {
        return Forum::query()
            ->withCount([
                'groups',
                'members',
                'admins',
                'reports as open_reports_count' => fn ($q) => $q->whereIn('status', ['new', 'in_review']),
            ])
            ->findOrFail($id);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|null>, extra: array<string, mixed>}
     */
    public function listFor(User $user, string $scope, int $page, int $perPage, array $filters): array
    {
        if ($scope === 'manage') {
            return $this->manageList($user, $page, $perPage, $filters);
        }

        return $this->visibleList($user, $page, $perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(User $actor, array $data, $logo = null): Forum
    {
        $status = ($data['status'] ?? '') === 'published' ? ForumStatus::ACTIVE : ForumStatus::DRAFT;

        $forum = Forum::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'details' => $data['details'] ?? null,
            'visibility' => $data['visibility'] ?? ForumVisibility::COMPANY->value,
            'branch_ids' => array_values($data['branch_ids'] ?? []),
            'project_ids' => array_values($data['project_ids'] ?? []),
            'status' => $status->value,
            'created_by' => $actor->id,
        ]);

        if ($logo !== null) {
            $forum->addMedia($logo)->toMediaCollection(Forum::LOGO_COLLECTION);
        }

        $this->replaceAdmins($forum, $data['admins'] ?? [], $actor, false);
        $this->replaceMembers($forum, $data['member_ids'] ?? [], $actor, false);

        $action = $status === ForumStatus::ACTIVE ? 'forum.published' : 'forum.updated';
        $this->activity->log((string) $forum->id, $actor, $action, 'forum', (string) $forum->id, $forum->name);

        if ($status === ForumStatus::ACTIVE) {
            $this->broadcastList($forum, 'published', (string) $actor->id);
            if (! empty($data['notify_members'])) {
                $this->push->notifyForumPublished($forum, $this->audience->ids($forum, (string) $actor->id));
            }
        }

        return $this->find((string) $forum->id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Forum $forum, User $actor, array $data, $logo = null): Forum
    {
        $before = [
            'name' => $forum->name,
            'description' => $forum->description,
            'visibility' => $forum->visibility instanceof ForumVisibility ? $forum->visibility->value : $forum->visibility,
            'branch_ids' => $forum->branch_ids ?? [],
            'project_ids' => $forum->project_ids ?? [],
        ];

        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'description' => array_key_exists('description', $data) ? $data['description'] : null,
            'details' => array_key_exists('details', $data) ? $data['details'] : null,
            'visibility' => $data['visibility'] ?? null,
        ], fn ($value) => $value !== null);

        if (array_key_exists('branch_ids', $data)) {
            $payload['branch_ids'] = array_values($data['branch_ids'] ?? []);
        }
        if (array_key_exists('project_ids', $data)) {
            $payload['project_ids'] = array_values($data['project_ids'] ?? []);
        }

        $publishing = ($data['status'] ?? null) === 'published' && $forum->status === ForumStatus::DRAFT;
        if ($publishing) {
            $payload['status'] = ForumStatus::ACTIVE->value;
        }

        $payload = array_filter($payload, fn ($value) => $value !== null);

        if ($payload !== []) {
            $forum->update($payload);
        }

        if ($logo !== null) {
            $forum->addMedia($logo)->toMediaCollection(Forum::LOGO_COLLECTION);
        }

        $this->activity->log(
            (string) $forum->id,
            $actor,
            $publishing ? 'forum.published' : 'forum.updated',
            'forum',
            (string) $forum->id,
            $forum->name,
            $before,
            [
                'name' => $forum->name,
                'description' => $forum->description,
                'visibility' => $forum->visibility instanceof ForumVisibility ? $forum->visibility->value : $forum->visibility,
                'branch_ids' => $forum->branch_ids ?? [],
                'project_ids' => $forum->project_ids ?? [],
            ],
        );

        $this->broadcastList($forum, $publishing ? 'published' : 'updated', (string) $actor->id);
        if ($publishing && ! empty($data['notify_members'])) {
            $this->push->notifyForumPublished($forum, $this->audience->ids($forum, (string) $actor->id));
        }

        return $this->find((string) $forum->id);
    }

    public function archive(Forum $forum, User $actor): Forum
    {
        $recipients = $this->audience->ids($forum);
        $forum->update(['status' => ForumStatus::ARCHIVED->value, 'archived_at' => now()]);
        $this->activity->log((string) $forum->id, $actor, 'forum.archived', 'forum', (string) $forum->id, $forum->name);
        $this->broadcastList($forum, 'archived', (string) $actor->id, $recipients);

        return $this->find((string) $forum->id);
    }

    public function restore(Forum $forum, User $actor): Forum
    {
        $forum->update(['status' => ForumStatus::ACTIVE->value, 'archived_at' => null]);
        $this->activity->log((string) $forum->id, $actor, 'forum.restored', 'forum', (string) $forum->id, $forum->name);
        $this->broadcastList($forum, 'published', (string) $actor->id);

        return $this->find((string) $forum->id);
    }

    /**
     * @param array<int, array<string, mixed>> $admins
     */
    public function replaceAdmins(Forum $forum, array $admins, User $actor, bool $log = true): void
    {
        $ids = $this->companyUserIds(array_column($admins, 'user_id'));
        $incoming = collect($admins)->keyBy('user_id');

        DB::transaction(function () use ($forum, $ids, $incoming) {
            $forum->admins()->whereNotIn('user_id', $ids)->delete();
            foreach ($ids as $userId) {
                $row = $incoming[$userId] ?? [];
                $forum->admins()->updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'can_manage_groups' => (bool) ($row['can_manage_groups'] ?? true),
                        'can_publish_announcements' => (bool) ($row['can_publish_announcements'] ?? true),
                    ],
                );
            }
        });

        if ($log) {
            $this->activity->log((string) $forum->id, $actor, 'forum.admins_updated', 'forum', (string) $forum->id, $forum->name);
        }
    }

    /**
     * @param array<int, string> $memberIds
     */
    public function replaceMembers(Forum $forum, array $memberIds, User $actor, bool $log = true): void
    {
        $ids = $this->companyUserIds($memberIds);
        $existing = $forum->members()->pluck('user_id')->map(fn ($id) => (string) $id);
        $removed = $existing->diff($ids)->values()->all();

        DB::transaction(function () use ($forum, $ids, $removed) {
            if ($removed !== []) {
                $forum->members()->whereIn('user_id', $removed)->delete();
                $groupIds = $forum->groups()->pluck('id');
                ForumGroupMember::query()->whereIn('group_id', $groupIds)->whereIn('user_id', $removed)->delete();
            }

            $current = $forum->members()->pluck('user_id')->map(fn ($id) => (string) $id);
            foreach (collect($ids)->diff($current) as $userId) {
                $forum->members()->create(['user_id' => $userId]);
            }
        });

        if ($removed !== []) {
            event(new ForumMembershipChanged($removed, (string) $forum->id, 'removed', (string) $forum->id));
        }

        if ($log) {
            $this->activity->log((string) $forum->id, $actor, 'forum.members_updated', 'forum', (string) $forum->id, $forum->name);
        }
    }

    /**
     * @param array<int, array{group_id: string, role?: string}> $groups
     */
    public function distribute(Forum $forum, string $userId, array $groups, User $actor): void
    {
        $isMember = $forum->members()->where('user_id', $userId)->exists()
            || $forum->admins()->where('user_id', $userId)->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'user_id' => [__('forum::forum.not_forum_member')],
            ]);
        }

        $allowed = $forum->groups()->pluck('id')->map(fn ($id) => (string) $id);
        $incoming = collect($groups)
            ->filter(fn ($row) => $allowed->contains((string) ($row['group_id'] ?? '')))
            ->keyBy(fn ($row) => (string) $row['group_id']);

        $current = ForumGroupMember::query()
            ->where('user_id', $userId)
            ->whereIn('group_id', $allowed)
            ->get()
            ->keyBy('group_id');

        DB::transaction(function () use ($incoming, $current, $userId) {
            $removed = $current->keys()->diff($incoming->keys());
            if ($removed->isNotEmpty()) {
                ForumGroupMember::query()->where('user_id', $userId)->whereIn('group_id', $removed)->delete();
            }

            foreach ($incoming as $groupId => $row) {
                $role = ($row['role'] ?? ForumMemberRole::MEMBER->value) === ForumMemberRole::ADMIN->value
                    ? ForumMemberRole::ADMIN->value
                    : ForumMemberRole::MEMBER->value;

                if ($current->has($groupId)) {
                    $current[$groupId]->update(['role' => $role, 'can_send' => $role === ForumMemberRole::ADMIN->value]);
                    continue;
                }

                ForumGroupMember::query()->create([
                    'group_id' => $groupId,
                    'user_id' => $userId,
                    'role' => $role,
                    'can_send' => $role === ForumMemberRole::ADMIN->value,
                    'last_read_at' => now(),
                ]);
            }
        });

        event(new ForumMembershipChanged([$userId], (string) $forum->id, 'updated', (string) $forum->id));
        $this->activity->log((string) $forum->id, $actor, 'forum.members_updated', 'forum', (string) $forum->id, $forum->name);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|null>}
     */
    public function members(Forum $forum, int $page, int $perPage, array $filters): array
    {
        $groupIds = $forum->groups()->pluck('id');
        $memberships = ForumGroupMember::query()
            ->whereIn('group_id', $groupIds)
            ->with('group:id,name')
            ->get()
            ->groupBy('user_id');

        $adminIds = $forum->admins()->pluck('user_id')->map(fn ($id) => (string) $id);
        $memberIds = $forum->members()->pluck('user_id')->map(fn ($id) => (string) $id);
        $userIds = $adminIds->merge($memberIds)->unique()->values();

        $users = User::query()
            ->with(['professionalData.jobTitle', 'professionalData.branch', 'managementHierarchy'])
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy(fn (User $user) => (string) $user->id);

        $rows = $userIds->map(function (string $userId) use ($users, $adminIds, $memberships) {
            $user = $users->get($userId);
            if ($user === null) {
                return null;
            }

            $groups = ($memberships->get($userId) ?? collect())->map(fn (ForumGroupMember $member) => [
                'id' => $member->group_id,
                'name' => $member->group?->name,
                'role' => $member->role instanceof ForumMemberRole ? $member->role->value : $member->role,
            ])->values()->all();

            return ForumPresenter::person($user) + [
                'forum_role' => $adminIds->contains($userId) ? 'admin' : 'member',
                'groups' => $groups,
                'status' => 'active',
            ];
        })->filter()->values();

        $search = trim((string) ($filters['search'] ?? ''));
        $branchId = (string) ($filters['branch_id'] ?? '');
        $groupId = (string) ($filters['group_id'] ?? '');

        $rows = $rows->filter(function (array $row) use ($search, $branchId, $groupId, $users) {
            if ($search !== '' && ! str_contains(mb_strtolower($row['name'] ?? ''), mb_strtolower($search)) && ! str_contains(mb_strtolower((string) ($row['email'] ?? '')), mb_strtolower($search))) {
                return false;
            }
            if ($branchId !== '') {
                $user = $users->get($row['user_id']);
                $candidates = array_filter([
                    (string) ($user?->professionalData?->branch_id ?? ''),
                    (string) ($user?->professionalData?->management_id ?? ''),
                    (string) ($user?->management_hierarchy_id ?? ''),
                ]);
                if (! in_array($branchId, $candidates, true)) {
                    return false;
                }
            }
            if ($groupId !== '' && ! collect($row['groups'])->contains(fn ($group) => (string) $group['id'] === $groupId)) {
                return false;
            }

            return true;
        })->values();

        return $this->page($rows, $page, $perPage);
    }

    /**
     * @param array<int, string> $userIds
     * @return array<int, string>
     */
    public function companyUserIds(array $userIds): array
    {
        return User::query()
            ->where('company_id', tenant('id'))
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|null>, extra: array<string, mixed>}
     */
    private function manageList(User $user, int $page, int $perPage, array $filters): array
    {
        $query = $this->managedQuery($user);

        $statsQuery = clone $query;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('status', ForumStatus::ACTIVE->value)->count(),
            'open_reports' => (int) DB::table('forum_reports')
                ->whereIn('forum_id', (clone $statsQuery)->select('forums.id'))
                ->whereIn('status', ['new', 'in_review'])
                ->count(),
            'archived' => (clone $statsQuery)->where('status', ForumStatus::ARCHIVED->value)->count(),
        ];

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }
        if (in_array($filters['status'] ?? null, ['draft', 'active', 'archived'], true)) {
            $query->where('status', $filters['status']);
        }
        if (in_array($filters['visibility'] ?? null, ['company', 'scoped'], true)) {
            $query->where('visibility', $filters['visibility']);
        }
        if (! empty($filters['branch_id'])) {
            $query->whereJsonContains('branch_ids', $filters['branch_id']);
        }

        $paginator = $query
            ->withCount([
                'groups',
                'members',
                'admins',
                'reports as open_reports_count' => fn ($q) => $q->whereIn('status', ['new', 'in_review']),
            ])
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $paginator->getCollection()->map(fn (Forum $forum) => ForumPresenter::forum($forum, $user, $this->access))->all(),
            'pagination' => ForumAnnouncementService::pagination($paginator),
            'extra' => [
                'can_create' => $this->access->allows($user, Permission::FORUM_CREATE()),
                'stats' => $stats,
            ],
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int|null>, extra: array<string, mixed>}
     */
    private function visibleList(User $user, int $page, int $perPage): array
    {
        $forums = Forum::query()
            ->where('status', ForumStatus::ACTIVE->value)
            ->withCount('groups')
            ->get()
            ->filter(fn (Forum $forum) => $this->access->canSeeForum($user, $forum))
            ->values();

        $ids = $forums->pluck('id')->all();
        $previews = $this->previews((string) $user->id, $ids);
        $unread = $this->unreadByForum((string) $user->id, $ids);

        $forums = $forums->sortByDesc(fn (Forum $forum) => $previews[(string) $forum->id]['created_at'] ?? $forum->created_at?->toISOString() ?? '')->values();
        $paged = $this->page($forums, $page, $perPage);

        $paged['items'] = collect($paged['items'])->map(function (Forum $forum) use ($previews, $unread) {
            $id = (string) $forum->id;

            return [
                'id' => $forum->id,
                'name' => $forum->name,
                'description' => $forum->description,
                'logo_url' => $forum->getFirstMediaUrl(Forum::LOGO_COLLECTION) ?: null,
                'visibility' => $forum->visibility instanceof ForumVisibility ? $forum->visibility->value : $forum->visibility,
                'unread_count' => $unread[$id] ?? 0,
                'groups_count' => (int) ($forum->groups_count ?? 0),
                'last_message' => $previews[$id] ?? null,
            ];
        })->all();
        $paged['extra'] = [];

        return $paged;
    }

    private function managedQuery(User $user)
    {
        return Forum::query()->where('company_id', tenant('id'));
    }

    /**
     * @param array<int, string> $forumIds
     * @return array<string, array<string, mixed>>
     */
    private function previews(string $userId, array $forumIds): array
    {
        if ($forumIds === []) {
            return [];
        }

        $rows = DB::table('forum_messages as m')
            ->join('forum_groups as g', 'g.id', '=', 'm.group_id')
            ->join('forum_group_members as gm', function ($join) use ($userId) {
                $join->on('gm.group_id', '=', 'g.id')->where('gm.user_id', '=', $userId);
            })
            ->join('users as u', 'u.id', '=', 'm.sender_id')
            ->whereIn('g.forum_id', $forumIds)
            ->where('g.status', ForumStatus::ACTIVE->value)
            ->orderByDesc('m.created_at')
            ->get(['g.forum_id', 'g.id as group_id', 'g.name as group_name', 'm.id', 'm.type', 'm.body', 'm.created_at', 'u.id as sender_id', 'u.name as sender_name']);

        $out = [];
        foreach ($rows as $row) {
            $forumId = (string) $row->forum_id;
            if (isset($out[$forumId])) {
                continue;
            }
            $out[$forumId] = [
                'group_id' => $row->group_id,
                'group_name' => $row->group_name,
                'type' => $row->type,
                'preview' => (string) $row->body,
                'sender' => ['id' => $row->sender_id, 'name' => $row->sender_name],
                'created_at' => $row->created_at,
            ];
        }

        return $out;
    }

    /**
     * @param array<int, string> $forumIds
     * @return array<string, int>
     */
    private function unreadByForum(string $userId, array $forumIds): array
    {
        if ($forumIds === []) {
            return [];
        }

        $messages = DB::table('forum_messages as m')
            ->join('forum_groups as g', 'g.id', '=', 'm.group_id')
            ->join('forum_group_members as gm', function ($join) use ($userId) {
                $join->on('gm.group_id', '=', 'm.group_id')->where('gm.user_id', '=', $userId);
            })
            ->whereIn('g.forum_id', $forumIds)
            ->where('m.sender_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('gm.last_read_at')->orWhereColumn('m.created_at', '>', 'gm.last_read_at');
            })
            ->groupBy('g.forum_id')
            ->selectRaw('g.forum_id, count(*) as total')
            ->pluck('total', 'forum_id');

        $announcements = DB::table('forum_announcements as a')
            ->whereIn('a.forum_id', $forumIds)
            ->where('a.status', 'published')
            ->whereNotExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('forum_announcement_reads as r')
                    ->whereColumn('r.announcement_id', 'a.id')
                    ->where('r.user_id', $userId);
            })
            ->groupBy('a.forum_id')
            ->selectRaw('a.forum_id, count(*) as total')
            ->pluck('total', 'forum_id');

        $out = [];
        foreach ($forumIds as $id) {
            $out[(string) $id] = (int) ($messages[$id] ?? 0) + (int) ($announcements[$id] ?? 0);
        }

        return $out;
    }

    /**
     * @param array<int, string>|null $userIds
     */
    private function broadcastList(Forum $forum, string $change, ?string $except = null, ?array $userIds = null): void
    {
        $userIds ??= $this->audience->ids($forum, $except);
        if ($userIds === []) {
            return;
        }

        event(new ForumListChanged(array_values($userIds), (string) $forum->id, $change));
    }

    /**
     * @param Collection<int, mixed> $rows
     * @return array{items: array<int, mixed>, pagination: array<string, int|null>}
     */
    private function page(Collection $rows, int $page, int $perPage): array
    {
        $total = $rows->count();
        $last = max(1, (int) ceil($total / $perPage));
        $page = min($page, $last);

        return [
            'items' => $rows->slice(($page - 1) * $perPage, $perPage)->values()->all(),
            'pagination' => [
                'page' => $page,
                'next_page' => $page < $last ? $page + 1 : null,
                'last_page' => $total === 0 ? 1 : $last,
                'result_count' => $total,
            ],
        ];
    }
}

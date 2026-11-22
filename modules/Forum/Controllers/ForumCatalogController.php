<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Models\Forum;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Forum\Models\ForumActivityLog;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\Forum\Requests\DistributeForumMemberRequest;
use Modules\Forum\Requests\SaveForumRequest;
use Modules\Forum\Requests\SyncForumAdminsRequest;
use Modules\Forum\Requests\SyncForumMemberIdsRequest;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumAnnouncementService;
use Modules\Forum\Services\ForumCatalogService;
use Modules\Forum\Services\ForumGroupService;
use Modules\User\Models\User;

class ForumCatalogController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumCatalogService $catalog,
        private ForumGroupService $groups,
        private ForumAnnouncementService $announcements,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $scope = $request->query('scope') === 'manage' ? 'manage' : 'visible';
        if ($scope === 'manage') {
            $this->access->assertAllows($user, Permission::FORUM_LIST());
        }
        $result = $this->catalog->listFor(
            $user,
            $scope,
            max(1, (int) $request->query('page', 1)),
            max(1, min((int) $request->query('per_page', 15), 100)),
            $request->only(['search', 'status', 'visibility', 'branch_id']),
        );

        return Json::items($result['items'], $result['extra'], paginationSettings: $result['pagination']);
    }

    public function store(SaveForumRequest $request): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_CREATE());

        $forum = $this->catalog->create($request->user(), $request->validated(), $request->file('logo'));

        return Json::created($this->detail($forum, $request->user()));
    }

    public function show(Request $request, string $forumId): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_VIEW());
        $forum = $this->managed($request, $forumId);

        return Json::item($this->detail($forum, $request->user()));
    }

    public function update(SaveForumRequest $request, string $forumId): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_UPDATE());
        $forum = $this->managed($request, $forumId);
        $forum = $this->catalog->update($forum, $request->user(), $request->validated(), $request->file('logo'));

        return Json::item($this->detail($forum, $request->user()));
    }

    public function archive(Request $request, string $forumId): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_ARCHIVE());
        $forum = $this->managed($request, $forumId);

        return Json::item(ForumPresenter::forum($this->catalog->archive($forum, $request->user()), $request->user(), $this->access));
    }

    public function restore(Request $request, string $forumId): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_RESTORE());
        $forum = $this->managed($request, $forumId);

        return Json::item(ForumPresenter::forum($this->catalog->restore($forum, $request->user()), $request->user(), $this->access));
    }

    public function overview(Request $request, string $forumId): JsonResponse
    {
        $forum = $this->open($request, $forumId);
        $user = $request->user();
        $rows = $this->groups->listFor($user, 'all', null, (string) $forum->id, null, true);
        $present = fn ($row) => ForumPresenter::group($row['group'], $row['access'], $row['unread']);
        $latest = $this->announcements->query((string) $forum->id, 'published')->with(['author', 'media'])->latest()->first();

        return Json::item([
            'forum' => [
                'id' => $forum->id,
                'name' => $forum->name,
                'description' => $forum->description,
                'logo_url' => $forum->getFirstMediaUrl(Forum::LOGO_COLLECTION) ?: null,
            ],
            'is_forum_admin' => $this->access->isForumManager($user, (string) $forum->id),
            'capabilities' => $this->access->capabilities($user),
            'announcements' => [
                'unread_count' => $this->announcements->unreadCount($user, (string) $forum->id),
                'latest' => $latest ? ForumPresenter::announcement($latest) : null,
            ],
            'my_groups' => $rows->filter(fn ($r) => $r['access']['is_member'])->map($present)->values()->all(),
            'other_groups' => $rows->reject(fn ($r) => $r['access']['is_member'])->map($present)->values()->all(),
        ]);
    }

    public function access(Request $request, string $forumId): JsonResponse
    {
        $forum = $this->open($request, $forumId);
        $user = $request->user();

        return Json::item([
            'is_forum_admin' => $this->access->isForumAdmin($user),
            'can_publish_announcements' => $this->access->allows($user, Permission::FORUM_ANNOUNCEMENT_CREATE()),
            'can_create_groups' => $this->access->allows($user, Permission::FORUM_GROUP_CREATE()),
            'capabilities' => $this->access->capabilities($user),
            'groups' => $this->groups->listFor($user, 'all', null, (string) $forum->id, null, true)->pluck('access')->values()->all(),
        ]);
    }

    public function admins(Request $request, string $forumId): JsonResponse
    {
        $forum = $this->managed($request, $forumId);
        $admins = $forum->admins()->with(['user.professionalData.jobTitle', 'user.professionalData.branch', 'user.managementHierarchy'])->get();

        return Json::items($admins->map(fn ($admin) => ForumPresenter::admin($admin))->all());
    }

    public function syncAdmins(SyncForumAdminsRequest $request, string $forumId): JsonResponse
    {
        $forum = $this->managed($request, $forumId);
        $this->access->assertAllows($request->user(), Permission::FORUM_ADMIN_UPDATE());
        $this->catalog->replaceAdmins($forum, $request->validated('admins'), $request->user());

        return $this->admins($request, $forumId);
    }

    public function members(Request $request, string $forumId): JsonResponse
    {
        $forum = $this->managed($request, $forumId);
        $result = $this->catalog->members(
            $forum,
            max(1, (int) $request->query('page', 1)),
            max(1, min((int) $request->query('per_page', 15), 100)),
            $request->only(['search', 'branch_id', 'group_id']),
        );

        return Json::items($result['items'], paginationSettings: $result['pagination']);
    }

    public function syncMembers(SyncForumMemberIdsRequest $request, string $forumId): JsonResponse
    {
        $forum = $this->managed($request, $forumId);
        $this->access->assertAllows($request->user(), Permission::FORUM_MEMBER_UPDATE());
        $this->catalog->replaceMembers($forum, $request->validated('member_ids'), $request->user());

        return Json::item(['member_ids' => $forum->members()->pluck('user_id')->all()]);
    }

    public function distribute(DistributeForumMemberRequest $request, string $forumId, string $userId): JsonResponse
    {
        $forum = $this->managed($request, $forumId);
        $this->access->assertAllows($request->user(), Permission::FORUM_MEMBER_UPDATE());
        $this->catalog->distribute($forum, $userId, $request->validated('groups'), $request->user());

        return Json::item(['user_id' => $userId]);
    }

    public function activity(Request $request, string $forumId): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_ACTIVITY_LIST());
        $this->catalog->find($forumId);
        $query = ForumActivityLog::query()->with('actor')->where('forum_id', $forumId);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")->orWhere('target_label', 'like', "%{$search}%");
            });
        }
        if ($actor = $request->query('actor_id')) {
            $query->where('actor_id', $actor);
        }
        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $paginator = $query->latest()->paginate(
            max(1, min((int) $request->query('per_page', 15), 100)),
            ['*'],
            'page',
            max(1, (int) $request->query('page', 1)),
        );

        return Json::items(
            $paginator->getCollection()->map(fn (ForumActivityLog $log) => ForumPresenter::activity($log))->all(),
            paginationSettings: ForumAnnouncementService::pagination($paginator),
        );
    }

    private function managed(Request $request, string $forumId): Forum
    {
        return $this->catalog->find($forumId);
    }

    private function open(Request $request, string $forumId): Forum
    {
        $forum = $this->catalog->find($forumId);
        $this->access->assertCanOpen($request->user(), $forum);

        return $forum;
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Forum $forum, User $user): array
    {
        $forum->load(['admins.user', 'members']);

        return ForumPresenter::forum($forum, $user, $this->access) + [
            'admins' => $forum->admins->map(fn ($admin) => [
                'user_id' => $admin->user_id,
                'name' => $admin->user?->name,
                'can_manage_groups' => $admin->can_manage_groups,
                'can_publish_announcements' => $admin->can_publish_announcements,
            ])->all(),
            'member_ids' => $forum->members->pluck('user_id')->all(),
        ];
    }
}

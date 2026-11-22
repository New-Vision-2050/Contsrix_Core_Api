<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Enums\ForumStatus;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Forum\Models\Forum;
use Modules\Forum\Models\ForumGroup;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\Forum\Requests\SaveForumGroupRequest;
use Modules\Forum\Requests\SyncForumMembersRequest;
use Modules\Forum\Requests\UpdateForumSendSettingsRequest;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumGroupService;
use Modules\User\Models\User;

class ForumGroupController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumGroupService $groups,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $scope = in_array($request->query('scope'), ['mine', 'others'], true) ? $request->query('scope') : 'all';

        $forum = $this->forum($request);
        $manager = $forum !== null && $this->access->isForumManager($request->user(), (string) $forum->id);
        $rows = $this->groups->listFor(
            $request->user(),
            $scope,
            $request->query('search'),
            $forum?->id,
            $request->query('status'),
            $forum !== null && ! $manager,
            $request->query('send_permission'),
        );

        $extra = [];
        if ($forum !== null) {
            $base = ForumGroup::query()->where('forum_id', $forum->id);
            $extra['stats'] = [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'active')->count(),
                'archived' => (clone $base)->where('status', 'archived')->count(),
                'admins_only' => (clone $base)->where('send_permission', 'admins_only')->count(),
            ];
        }

        return Json::items(
            $rows->map(fn ($r) => ForumPresenter::group($r['group'], $r['access'], $r['unread']))->all(),
            $extra,
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return Json::item($this->present($request->user(), $this->groups->find($id, $this->forumId($request))));
    }

    public function store(SaveForumGroupRequest $request): JsonResponse
    {
        $forum = $this->forum($request);
        $data = $request->groupData();
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_CREATE());
        if ($forum !== null) {
            $data['forum_id'] = $forum->id;
        }

        $group = $this->groups->create(
            $data,
            $request->user(),
            $request->file('icon'),
            $request->validated('members', []),
        );

        return Json::created($this->present($request->user(), $this->groups->find($group->id)));
    }

    public function update(SaveForumGroupRequest $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_UPDATE());
        $group = $this->groups->find($id, $this->forumId($request));
        $this->groups->update($group, $request->groupData(), $request->file('icon'), $request->user());

        if ($request->has('members')) {
            $this->groups->syncMembers($group, $request->validated('members'), $request->user());
        }

        return Json::item($this->present($request->user(), $this->groups->find($id, $this->forumId($request))));
    }

    public function archive(Request $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_ARCHIVE());
        $group = $this->groups->setStatus($this->groups->find($id, $this->forumId($request)), ForumStatus::ARCHIVED, $request->user());

        return Json::item($this->present($request->user(), $group));
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_RESTORE());
        $group = $this->groups->setStatus($this->groups->find($id, $this->forumId($request)), ForumStatus::ACTIVE, $request->user());

        return Json::item($this->present($request->user(), $group));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_DELETE());

        $this->groups->delete($this->groups->find($id, $this->forumId($request)));

        return Json::deleted();
    }

    public function members(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id, $this->forumId($request));
        $this->access->assertCanView($group, $request->user());

        $members = $group->members()->with(['user.professionalData.jobTitle', 'user.professionalData.branch', 'user.managementHierarchy'])->get()
            ->sortBy(fn ($m) => [$m->role->value === 'admin' ? 0 : 1, $m->user?->name])
            ->values();

        return Json::items($members->map(fn ($m) => ForumPresenter::member($m))->all());
    }

    public function syncMembers(SyncForumMembersRequest $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_MEMBER_UPDATE());

        $group = $this->groups->find($id, $this->forumId($request));
        $this->groups->syncMembers($group, $request->validated('members'), $request->user());

        return Json::item($this->present($request->user(), $this->groups->find($id, $this->forumId($request))));
    }

    public function updateSendSettings(UpdateForumSendSettingsRequest $request, string $id): JsonResponse
    {
        $this->access->assertAllows($request->user(), Permission::FORUM_GROUP_UPDATE());

        $group = $this->groups->find($id, $this->forumId($request));
        $this->groups->updateSendSettings(
            $group,
            $request->safe()->only(['send_permission', 'allow_images', 'allow_voice', 'allow_files']),
            $request->validated('allowed_user_ids', []),
            $request->user(),
        );

        return Json::item($this->present($request->user(), $this->groups->find($id, $this->forumId($request))));
    }

    public function access(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id, $this->forumId($request));

        return Json::item($this->access->accessFor($group, $request->user(), $this->access->membership($group, $request->user())));
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id, $this->forumId($request));
        $this->access->assertCanView($group, $request->user());
        $this->groups->markRead($group, $request->user());

        return Json::item(['group_id' => $group->id, 'unread_count' => 0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user, ForumGroup $group): array
    {
        $member = $this->access->membership($group, $user);
        $unread = $this->groups->unreadCounts((string) $user->id)[$group->id] ?? 0;

        $data = ForumPresenter::group($group, $this->access->accessFor($group, $user, $member), $unread);

        if ($this->access->isForumManager($user, (string) ($group->forum_id ?? '')) || $this->access->isForumAdmin($user)) {
            $data['allowed_user_ids'] = $group->members()->where('can_send', true)->pluck('user_id')->all();
        }

        return $data;
    }

    private function forumId(Request $request): ?string
    {
        $id = $request->route('forumId');

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function forum(Request $request): ?Forum
    {
        $id = $this->forumId($request);
        if ($id === null) {
            return null;
        }

        $forum = Forum::query()->findOrFail($id);
        $this->access->assertCanOpen($request->user(), $forum);

        return $forum;
    }
}

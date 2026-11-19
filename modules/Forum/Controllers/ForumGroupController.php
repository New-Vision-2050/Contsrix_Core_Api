<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $rows = $this->groups->listFor($request->user(), $scope, $request->query('search'));

        return Json::items(
            $rows->map(fn ($r) => ForumPresenter::group($r['group'], $r['access'], $r['unread']))->all()
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return Json::item($this->present($request->user(), $this->groups->find($id)));
    }

    public function store(SaveForumGroupRequest $request): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $group = $this->groups->create(
            $request->groupData(),
            $request->user(),
            $request->file('icon'),
            $request->validated('members', []),
        );

        return Json::created($this->present($request->user(), $this->groups->find($group->id)));
    }

    public function update(SaveForumGroupRequest $request, string $id): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $group = $this->groups->find($id);
        $this->groups->update($group, $request->groupData(), $request->file('icon'));

        if ($request->has('members')) {
            $this->groups->syncMembers($group, $request->validated('members'));
        }

        return Json::item($this->present($request->user(), $this->groups->find($id)));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $this->groups->delete($this->groups->find($id));

        return Json::deleted();
    }

    public function members(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id);
        $this->access->assertCanView($group, $request->user());

        $members = $group->members()->with('user')->get()
            ->sortBy(fn ($m) => [$m->role->value === 'admin' ? 0 : 1, $m->user?->name])
            ->values();

        return Json::items($members->map(fn ($m) => ForumPresenter::member($m))->all());
    }

    public function syncMembers(SyncForumMembersRequest $request, string $id): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $group = $this->groups->find($id);
        $this->groups->syncMembers($group, $request->validated('members'));

        return Json::item($this->present($request->user(), $this->groups->find($id)));
    }

    public function updateSendSettings(UpdateForumSendSettingsRequest $request, string $id): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $group = $this->groups->find($id);
        $this->groups->updateSendSettings(
            $group,
            $request->safe()->only(['send_permission', 'allow_images', 'allow_voice', 'allow_files']),
            $request->validated('allowed_user_ids', []),
        );

        return Json::item($this->present($request->user(), $this->groups->find($id)));
    }

    public function access(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id);

        return Json::item($this->access->accessFor($group, $request->user(), $this->access->membership($group, $request->user())));
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $group = $this->groups->find($id);
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

        if ($this->access->isForumAdmin($user)) {
            $data['allowed_user_ids'] = $group->members()->where('can_send', true)->pluck('user_id')->all();
        }

        return $data;
    }
}

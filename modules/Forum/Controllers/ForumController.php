<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumAnnouncementService;
use Modules\Forum\Services\ForumGroupService;
use Modules\User\Models\User;

class ForumController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumGroupService $groups,
        private ForumAnnouncementService $announcements,
    ) {}

    /**
     * Forum home screen: announcements card, joined groups and locked groups.
     */
    public function overview(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $rows = $this->groups->listFor($user);
        $present = fn ($row) => ForumPresenter::group($row['group'], $row['access'], $row['unread']);

        $latest = $this->announcements->query()->with(['author', 'media'])->latest()->first();

        return Json::item([
            'is_forum_admin' => $this->access->isForumAdmin($user),
            'announcements' => [
                'unread_count' => $this->announcements->unreadCount($user),
                'latest' => $latest ? ForumPresenter::announcement($latest) : null,
            ],
            'my_groups' => $rows->filter(fn ($r) => $r['access']['is_member'])->map($present)->values()->all(),
            'other_groups' => $rows->reject(fn ($r) => $r['access']['is_member'])->map($present)->values()->all(),
        ]);
    }

    /**
     * Permissions of the current user on every group (member / role / can send).
     */
    public function access(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return Json::item([
            'is_forum_admin' => $this->access->isForumAdmin($user),
            'can_publish_announcements' => $this->access->allows($user, Permission::FORUM_ANNOUNCEMENT_CREATE()),
            'can_create_groups' => $this->access->allows($user, Permission::FORUM_GROUP_CREATE()),
            'groups' => $this->groups->listFor($user)->pluck('access')->values()->all(),
        ]);
    }

    /**
     * Employees picker used when managing group members (admins only).
     */
    public function employees(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->access->canPickEmployees($user), 403, __('forum::forum.only_admins'));

        $search = trim((string) $request->query('search', ''));
        $branchId = $request->query('branch_id');
        $projectId = $request->query('project_id');

        $users = User::query()
            ->with(['professionalData.jobTitle', 'professionalData.branch', 'managementHierarchy'])
            ->where('company_id', tenant('id'))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($branchId, fn ($q) => $q->where(function ($q) use ($branchId) {
                $q->where('management_hierarchy_id', $branchId)
                    ->orWhereHas('professionalData', function ($professional) use ($branchId) {
                        $professional->where('branch_id', $branchId)
                            ->orWhere('management_id', $branchId)
                            ->orWhere('department_id', $branchId);
                    });
            }))
            ->when($projectId, fn ($q) => $q->whereHas('projects', fn ($projects) => $projects->where('projects.id', $projectId)))
            ->orderBy('name')
            ->limit(100)
            ->get();

        return Json::items($users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'job_title' => ForumPresenter::text($u->professionalData?->jobTitle?->name),
            'branch_name' => ForumPresenter::text($u->professionalData?->branch?->name ?? $u->managementHierarchy?->name),
            'avatar_url' => null,
        ])->all());
    }

    /**
     * Authorizes private Reverb channels (JWT based, for mobile and web clients).
     */
    public function broadcastingAuth(Request $request)
    {
        return Broadcast::auth($request);
    }
}

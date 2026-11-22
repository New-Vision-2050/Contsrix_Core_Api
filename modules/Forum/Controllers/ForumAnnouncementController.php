<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Models\Forum;
use Modules\RoleAndPermission\Enums\Permission;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\Forum\Requests\PublishForumAnnouncementRequest;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumAnnouncementService;

class ForumAnnouncementController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumAnnouncementService $announcements,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $forum = $this->forum($request, false);
        $manager = $forum !== null && (
            $this->access->allows($request->user(), Permission::FORUM_ANNOUNCEMENT_LIST())
            || $this->access->canPublishAnnouncements($request->user(), $forum)
            || $this->access->allows($request->user(), Permission::FORUM_ANNOUNCEMENT_DELETE())
        );
        $requested = $request->query('status');
        $status = $manager
            ? (is_string($requested) && $requested !== '' ? $requested : null)
            : 'published';

        $result = $this->announcements->paginate(
            $request->user(),
            max(1, (int) $request->query('page', 1)),
            max(1, min((int) $request->query('per_page', 15), 100)),
            $forum?->id,
            $status,
            $request->query('search'),
        );

        $extra = ['unread_count' => $this->announcements->unreadCount($request->user(), $forum?->id)];
        if ($forum !== null) {
            $extra['stats'] = $this->announcements->stats((string) $forum->id);
        }

        return Json::items(
            $result['items']->all(),
            $extra,
            paginationSettings: $result['pagination'],
        );
    }

    public function store(PublishForumAnnouncementRequest $request): JsonResponse
    {
        $forum = $this->forum($request, true);
        $this->access->assertAllows($request->user(), Permission::FORUM_ANNOUNCEMENT_CREATE());

        $announcement = $this->announcements->publish(
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
            $request->file('attachments', []) ?? [],
            $forum?->id,
            $request->validated()['status'] ?? 'published',
            $request->boolean('notify', true),
        );

        return Json::created(ForumPresenter::announcement($announcement, false));
    }

    public function update(PublishForumAnnouncementRequest $request, string $id): JsonResponse
    {
        $forum = $this->forum($request, true);
        $this->access->assertAllows($request->user(), Permission::FORUM_ANNOUNCEMENT_UPDATE());

        $announcement = $this->announcements->update(
            $this->announcements->find($id, $forum?->id),
            $request->user(),
            $request->safe()->only(['title', 'body', 'status', 'notify']),
            $request->file('attachments', []) ?? [],
        );

        return Json::item(ForumPresenter::announcement($announcement, false));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $forum = $this->forum($request, false);
        $this->access->assertAllows($request->user(), Permission::FORUM_ANNOUNCEMENT_DELETE());

        $this->announcements->delete($this->announcements->find($id, $forum?->id), $request->user());

        return Json::deleted();
    }

    public function markRead(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['string'],
        ])['ids'] ?? null;

        $forum = $this->forum($request, false);
        $this->announcements->markRead($request->user(), $ids, $forum?->id);

        return Json::item(['unread_count' => $this->announcements->unreadCount($request->user(), $forum?->id)]);
    }

    private function forum(Request $request, bool $managersOnly): ?Forum
    {
        $id = $request->route('forumId');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $forum = Forum::query()->findOrFail($id);
        if ($managersOnly) {
            $this->access->assertCanPublish($request->user(), $forum);
        } else {
            $this->access->assertCanOpen($request->user(), $forum);
        }

        return $forum;
    }
}

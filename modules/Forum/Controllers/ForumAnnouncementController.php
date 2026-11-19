<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $result = $this->announcements->paginate(
            $request->user(),
            max(1, (int) $request->query('page', 1)),
            max(1, min((int) $request->query('per_page', 15), 100)),
        );

        return Json::items(
            $result['items']->all(),
            ['unread_count' => $this->announcements->unreadCount($request->user())],
            paginationSettings: $result['pagination'],
        );
    }

    public function store(PublishForumAnnouncementRequest $request): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $announcement = $this->announcements->publish(
            $request->user(),
            $request->validated('title'),
            $request->validated('body'),
            $request->file('attachments', []),
        );

        return Json::created(ForumPresenter::announcement($announcement, false));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->access->assertForumAdmin($request->user());

        $this->announcements->delete($this->announcements->find($id));

        return Json::deleted();
    }

    public function markRead(Request $request): JsonResponse
    {
        $ids = $request->validate([
            'ids' => ['sometimes', 'array'],
            'ids.*' => ['string'],
        ])['ids'] ?? null;

        $this->announcements->markRead($request->user(), $ids);

        return Json::item(['unread_count' => $this->announcements->unreadCount($request->user())]);
    }
}

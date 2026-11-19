<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Models\ForumMessage;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\Forum\Requests\SendForumMessageRequest;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumGroupService;
use Modules\Forum\Services\ForumMessageService;

class ForumMessageController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumGroupService $groups,
        private ForumMessageService $messages,
    ) {}

    public function index(Request $request, string $groupId): JsonResponse
    {
        $group = $this->groups->find($groupId);
        $this->access->assertCanView($group, $request->user());

        $limit = max(1, min((int) $request->query('limit', 30), 100));
        $result = $this->messages->list($group, $request->query('before_id'), $limit);

        return Json::items(
            $result['messages']->map(fn (ForumMessage $m) => ForumPresenter::message($m))->all(),
            ['has_more' => $result['has_more']],
        );
    }

    public function store(SendForumMessageRequest $request, string $groupId): JsonResponse
    {
        $group = $this->groups->find($groupId);

        $message = $this->messages->send(
            $group,
            $request->user(),
            $request->messageType(),
            $request->validated('body'),
            $request->file('attachment'),
            $request->validated('duration_seconds'),
        );

        return Json::created(ForumPresenter::message($message));
    }

    public function destroy(Request $request, string $groupId, string $messageId): JsonResponse
    {
        $group = $this->groups->find($groupId);
        $this->access->assertCanView($group, $request->user());

        $message = ForumMessage::query()->where('group_id', $group->id)->findOrFail($messageId);
        $this->messages->delete($message, $request->user());

        return Json::deleted();
    }
}

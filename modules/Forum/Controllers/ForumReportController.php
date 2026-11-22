<?php

declare(strict_types=1);

namespace Modules\Forum\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Forum\Models\ForumReport;
use Modules\Forum\Presenters\ForumPresenter;
use Modules\Forum\Requests\ResolveForumReportRequest;
use Modules\Forum\Services\ForumAccessService;
use Modules\Forum\Services\ForumAnnouncementService;
use Modules\Forum\Services\ForumCatalogService;
use Modules\Forum\Services\ForumReportService;

class ForumReportController extends Controller
{
    public function __construct(
        private ForumAccessService $access,
        private ForumCatalogService $catalog,
        private ForumReportService $reports,
    ) {}

    public function index(Request $request, string $forumId): JsonResponse
    {
        $forum = $this->catalog->find($forumId);
        $this->access->assertCanReview($request->user(), $forum);

        $query = ForumReport::query()
            ->with(['reporter', 'group', 'message.sender', 'message.media', 'message.group'])
            ->where('forum_id', $forumId);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('reason', 'like', '%' . $search . '%');
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($groupId = $request->query('group_id')) {
            $query->where('group_id', $groupId);
        }

        $base = ForumReport::query()->where('forum_id', $forumId);
        $stats = [
            'new' => (clone $base)->where('status', 'new')->count(),
            'in_review' => (clone $base)->where('status', 'in_review')->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
            'closed' => (clone $base)->where('status', 'closed')->count(),
        ];

        $paginator = $query->latest()->paginate(
            max(1, min((int) $request->query('per_page', 15), 100)),
            ['*'],
            'page',
            max(1, (int) $request->query('page', 1)),
        );

        return Json::items(
            $paginator->getCollection()->map(fn (ForumReport $report) => ForumPresenter::report($report))->all(),
            ['stats' => $stats],
            paginationSettings: ForumAnnouncementService::pagination($paginator),
        );
    }

    public function resolve(ResolveForumReportRequest $request, string $forumId, string $reportId): JsonResponse
    {
        $forum = $this->catalog->find($forumId);
        $this->access->assertCanReview($request->user(), $forum);
        $report = ForumReport::query()->with('message.group')->where('forum_id', $forumId)->findOrFail($reportId);

        $report = $this->reports->resolve(
            $forum,
            $report,
            $request->user(),
            $request->validated('action'),
            $request->validated('note'),
        );

        return Json::item(ForumPresenter::report($report->load(['reporter', 'group', 'message.sender', 'message.media', 'message.group'])));
    }
}

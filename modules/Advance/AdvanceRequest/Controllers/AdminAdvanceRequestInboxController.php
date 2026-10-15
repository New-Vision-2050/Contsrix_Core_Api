<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Advance\AdvanceRequest\Presenters\AdvanceRequestReportPresenter;
use Modules\Advance\AdvanceRequest\Requests\AdminRejectAdvanceRequestRequest;
use Modules\Advance\AdvanceRequest\Services\AdvanceRequestService;
use Modules\EmployeeTask\Presenters\InboxItemPresenter;

/**
 * Dedicated admin approver inbox for advance requests (طلبات السلف).
 *
 * Same criteria as api/v1/admin/employee-tasks/inbox: only PENDING requests
 * whose current pending workflow step authorizes the caller (step
 * assigned_user_id / authorized_user_ids). Approve/reject act on that step —
 * no permission middleware, exactly like the unified admin inbox.
 */
class AdminAdvanceRequestInboxController extends Controller
{
    public function __construct(
        private readonly AdvanceRequestService $advanceRequestService,
    ) {}

    /**
     * Pending advance requests awaiting the caller's approval (unified inbox shape).
     */
    public function inbox(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['date_from', 'date_to']);
        $perPage = max(1, min(100, (int) request()->input('per_page', 15)));
        $page = max(1, (int) request()->input('page', 1));

        $items = $this->advanceRequestService->inboxForApprover($adminId, $filters);

        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return Json::items(
            mainItems: $slice->map(fn ($advanceRequest) => InboxItemPresenter::fromAdvanceRequest($advanceRequest))->all(),
            paginationSettings: [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            message: 'Advance requests inbox retrieved successfully',
        );
    }

    /**
     * Badge counts for the caller's advance inbox.
     */
    public function inboxCounts(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['date_from', 'date_to']);

        $count = $this->advanceRequestService->inboxForApprover($adminId, $filters)->count();

        return Json::item([
            'pending_advance_requests' => $count,
            'total' => $count,
        ], message: 'Advance inbox counts retrieved successfully');
    }

    /**
     * Company-wide advance report table (every request, not only the caller's inbox).
     */
    public function report(): JsonResponse
    {
        $filters = request()->only(['user_id', 'status', 'advance_policy_id', 'date_from', 'date_to', 'search', 'page']);
        $perPage = (int) request()->input('per_page', 15);

        $paginator = $this->advanceRequestService->reportList($filters, $perPage);

        return Json::items(
            mainItems: AdvanceRequestReportPresenter::rows($paginator->items()),
            paginationSettings: [
                'page' => $paginator->currentPage(),
                'next_page' => min($paginator->currentPage() + 1, $paginator->lastPage()),
                'last_page' => $paginator->lastPage(),
                'result_count' => $paginator->total(),
            ],
            message: 'Advance request report retrieved successfully',
        );
    }

    /**
     * One advance request with the full workflow: every step, who was allowed
     * to act, and who actually approved or rejected it.
     */
    public function reportShow(string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->reportDetail($id);

        return Json::item(
            AdvanceRequestReportPresenter::detail($advanceRequest),
            message: 'Advance request report detail retrieved successfully',
        );
    }

    /**
     * Approve the caller's pending workflow step on an advance request.
     */
    public function approve(string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->approve($id);
        $advanceRequest->loadMissing([
            'user',
            'advancePolicy',
            'processes.steps.procedureSettingStep',
            'processes.steps.assignedUser',
        ]);

        return Json::item(
            InboxItemPresenter::fromAdvanceRequest($advanceRequest),
            message: 'Advance request approved successfully'
        );
    }

    /**
     * Reject the caller's pending workflow step on an advance request.
     */
    public function reject(AdminRejectAdvanceRequestRequest $request, string $id): JsonResponse
    {
        $advanceRequest = $this->advanceRequestService->reject($id, $request->rejectionReason());
        $advanceRequest->loadMissing([
            'user',
            'advancePolicy',
            'processes.steps.procedureSettingStep',
            'processes.steps.assignedUser',
        ]);

        return Json::item(
            InboxItemPresenter::fromAdvanceRequest($advanceRequest),
            message: 'Advance request rejected successfully'
        );
    }
}

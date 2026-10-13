<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\EmployeeTask\Presenters\InboxItemPresenter;
use Modules\Leave\LeaveRequest\Requests\AdminRejectLeaveRequestRequest;
use Modules\Leave\LeaveRequest\Services\LeaveRequestService;

/**
 * Dedicated admin approver inbox for leave requests (طلبات الإجازة).
 *
 * Same criteria as api/v1/admin/employee-tasks/inbox: only PENDING requests
 * whose current pending workflow step authorizes the caller (step
 * assigned_user_id / authorized_user_ids). Approve/reject act on that step —
 * no permission middleware, exactly like the unified admin inbox.
 */
class AdminLeaveRequestInboxController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    /**
     * Pending leave requests awaiting the caller's approval (unified inbox shape).
     */
    public function inbox(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['date_from', 'date_to']);
        $perPage = max(1, min(100, (int) request()->input('per_page', 15)));
        $page = max(1, (int) request()->input('page', 1));

        $items = $this->leaveRequestService->inboxForApprover($adminId, $filters);

        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return Json::items(
            mainItems: $slice->map(fn ($leaveRequest) => InboxItemPresenter::fromLeaveRequest($leaveRequest))->all(),
            paginationSettings: [
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
            message: 'Leave requests inbox retrieved successfully',
        );
    }

    /**
     * Badge counts for the caller's leave inbox.
     */
    public function inboxCounts(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['date_from', 'date_to']);

        $count = $this->leaveRequestService->inboxForApprover($adminId, $filters)->count();

        return Json::item([
            'pending_leave_requests' => $count,
            'total' => $count,
        ], message: 'Leave inbox counts retrieved successfully');
    }

    /**
     * Approve the caller's pending workflow step on a leave request.
     */
    public function approve(string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->approve($id);
        $leaveRequest->loadMissing([
            'user',
            'leavePolicy.leaveType',
            'processes.steps.procedureSettingStep',
            'processes.steps.assignedUser',
        ]);

        return Json::item(
            InboxItemPresenter::fromLeaveRequest($leaveRequest),
            message: 'Leave request approved successfully'
        );
    }

    /**
     * Reject the caller's pending workflow step on a leave request.
     */
    public function reject(AdminRejectLeaveRequestRequest $request, string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->reject($id, $request->rejectionReason());
        $leaveRequest->loadMissing([
            'user',
            'leavePolicy.leaveType',
            'processes.steps.procedureSettingStep',
            'processes.steps.assignedUser',
        ]);

        return Json::item(
            InboxItemPresenter::fromLeaveRequest($leaveRequest),
            message: 'Leave request rejected successfully'
        );
    }
}

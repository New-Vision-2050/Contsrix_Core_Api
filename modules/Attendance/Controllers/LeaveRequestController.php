<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use BasePackage\Shared\Presenters\Json;
use Modules\Attendance\Services\LeaveRequestService;
use Modules\Attendance\Requests\CreateLeaveRequestRequest;
use Modules\Attendance\Requests\UpdateLeaveRequestRequest;
use Modules\Attendance\Requests\GetLeaveRequestsRequest;
use Modules\Attendance\Requests\ApproveLeaveRequestRequest;
use Modules\Attendance\Requests\RejectLeaveRequestRequest;
use Modules\Attendance\Requests\MyLeaveRequestsRequest;
use Modules\Attendance\Requests\PendingApprovalsRequest;
use Modules\Attendance\Requests\LeaveCalendarRequest;
use Modules\Attendance\Requests\LeaveConflictCheckRequest;
use Modules\Attendance\Requests\LeaveBalanceRequest;
use Modules\Attendance\Presenters\LeaveRequestPresenter;

class LeaveRequestController extends Controller
{
    public function __construct(
        private LeaveRequestService $leaveRequestService,
        private LeaveRequestPresenter $leaveRequestPresenter,
    ) {}

    /**
     * Get all leave requests
     */
    public function index(GetLeaveRequestsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $leaveRequests = $this->leaveRequestService->getLeaveRequests($filters);

        $presentedData = LeaveRequestPresenter::collection($leaveRequests);

        return Json::items($presentedData, message: 'Leave requests retrieved successfully');
    }

    /**
     * Create a new leave request
     */
    public function store(CreateLeaveRequestRequest $request): JsonResponse
    {
        $createLeaveRequestDTO = $request->createLeaveRequestDTO();
        $leaveRequest = $this->leaveRequestService->createLeaveRequest($createLeaveRequestDTO);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request created successfully'
        );
    }

    /**
     * Get a specific leave request
     */
    public function show(string $leaveRequestId): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->getLeaveRequestById($leaveRequestId);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request retrieved successfully'
        );
    }

    /**
     * Update a leave request
     */
    public function update(UpdateLeaveRequestRequest $request, string $leaveRequestId): JsonResponse
    {
        $updateLeaveRequestDTO = $request->createUpdateLeaveRequestDTO();
        $leaveRequest = $this->leaveRequestService->updateLeaveRequest($leaveRequestId, $updateLeaveRequestDTO);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request updated successfully'
        );
    }

    /**
     * Cancel a leave request
     */
    public function cancel(string $leaveRequestId): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->cancelLeaveRequest($leaveRequestId);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request cancelled successfully'
        );
    }

    /**
     * Approve a leave request
     */
    public function approve(ApproveLeaveRequestRequest $request, string $leaveRequestId): JsonResponse
    {
        $approveDTO = $request->createApproveLeaveRequestDTO();

        $leaveRequest = $this->leaveRequestService->approveLeaveRequest(
            $leaveRequestId,
            $approveDTO->getApproverId(),
            $approveDTO->getNotes()
        );

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request approved successfully'
        );
    }

    /**
     * Reject a leave request
     */
    public function reject(RejectLeaveRequestRequest $request, string $leaveRequestId): JsonResponse
    {
        $rejectDTO = $request->createRejectLeaveRequestDTO();

        $leaveRequest = $this->leaveRequestService->rejectLeaveRequest(
            $leaveRequestId,
            $rejectDTO->getRejecterId(),
            $rejectDTO->getReason()
        );

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request rejected successfully'
        );
    }

    /**
     * Delete a leave request
     */
    public function destroy(string $leaveRequestId): JsonResponse
    {
        $this->leaveRequestService->deleteLeaveRequest($leaveRequestId);

        return Json::success('Leave request deleted successfully');
    }

    /**
     * Get my leave requests
     */
    public function myRequests(MyLeaveRequestsRequest $request): JsonResponse
    {
        $filterDTO = $request->createMyLeaveRequestsFilterDTO();

        $leaveRequests = $this->leaveRequestService->getLeaveRequests($filterDTO->toArray());

        $presentedData = LeaveRequestPresenter::collection($leaveRequests);

        return Json::items($presentedData, message: 'Your leave requests retrieved successfully');
    }

    /**
     * Get pending approvals (for managers/HR)
     */
    public function pendingApprovals(PendingApprovalsRequest $request): JsonResponse
    {
        $filterDTO = $request->createPendingApprovalsFilterDTO();

        $leaveRequests = $this->leaveRequestService->getPendingApprovals($filterDTO->toArray());

        $presentedData = LeaveRequestPresenter::collection($leaveRequests);

        return Json::items($presentedData, message: 'Pending leave requests retrieved successfully');
    }

    /**
     * Get leave calendar
     */
    public function calendar(LeaveCalendarRequest $request): JsonResponse
    {
        $calendarDTO = $request->createLeaveCalendarDTO();

        $calendar = $this->leaveRequestService->getLeaveCalendar(
            $calendarDTO->getStartDate(),
            $calendarDTO->getEndDate(),
            $calendarDTO->getCompanyId()
        );

        return Json::item($calendar, message: 'Leave calendar retrieved successfully');
    }

    /**
     * Check for leave conflicts
     */
    public function checkConflicts(LeaveConflictCheckRequest $request): JsonResponse
    {
        $conflictDTO = $request->createLeaveConflictCheckDTO();

        $conflicts = $this->leaveRequestService->checkLeaveConflicts(
            $conflictDTO->getUserId(),
            $conflictDTO->getStartDate(),
            $conflictDTO->getEndDate(),
            $conflictDTO->getExcludeRequestId()
        );

        return Json::item([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts
        ], message: 'Leave conflicts checked successfully');
    }

    /**
     * Get leave balance
     */
    public function getBalance(LeaveBalanceRequest $request): JsonResponse
    {
        $balanceDTO = $request->createLeaveBalanceDTO();

        $balance = $this->leaveRequestService->getLeaveBalance(
            $balanceDTO->getUserId(),
            $balanceDTO->getLeaveTypeId(),
            $balanceDTO->getYear()
        );

        return Json::item($balance, message: 'Leave balance retrieved successfully');
    }
}

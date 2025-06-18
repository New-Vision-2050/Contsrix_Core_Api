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
        
        $presentedData = $leaveRequests->map(function ($leaveRequest) {
            return (new LeaveRequestPresenter($leaveRequest))->getData();
        });
        
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
    public function approve(Request $request, string $leaveRequestId): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->approveLeaveRequest(
            $leaveRequestId,
            $request->user()->id,
            $request->input('notes')
        );
        
        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request approved successfully'
        );
    }

    /**
     * Reject a leave request
     */
    public function reject(Request $request, string $leaveRequestId): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->rejectLeaveRequest(
            $leaveRequestId,
            $request->user()->id,
            $request->input('reason', 'No reason provided')
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
    public function myRequests(Request $request): JsonResponse
    {
        $filters = array_merge(
            $request->only(['status', 'leave_type_id', 'start_date', 'end_date']),
            ['user_id' => $request->user()->id]
        );
        
        $leaveRequests = $this->leaveRequestService->getLeaveRequests($filters);
        
        $presentedData = $leaveRequests->map(function ($leaveRequest) {
            return (new LeaveRequestPresenter($leaveRequest))->getData();
        });
        
        return Json::items($presentedData, message: 'Your leave requests retrieved successfully');
    }

    /**
     * Get pending approvals (for managers/HR)
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        $filters = array_merge(
            $request->only(['leave_type_id', 'start_date', 'end_date']),
            ['status' => 'pending']
        );
        
        $leaveRequests = $this->leaveRequestService->getPendingApprovals($filters);
        
        $presentedData = $leaveRequests->map(function ($leaveRequest) {
            return (new LeaveRequestPresenter($leaveRequest))->getData();
        });
        
        return Json::items($presentedData, message: 'Pending leave requests retrieved successfully');
    }

    /**
     * Get leave calendar
     */
    public function calendar(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $companyId = $request->input('company_id');
        
        $calendar = $this->leaveRequestService->getLeaveCalendar($startDate, $endDate, $companyId);
        
        return Json::item($calendar, message: 'Leave calendar retrieved successfully');
    }

    /**
     * Check for leave conflicts
     */
    public function checkConflicts(Request $request): JsonResponse
    {
        $userId = $request->input('user_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $excludeRequestId = $request->input('exclude_request_id');
        
        $conflicts = $this->leaveRequestService->checkLeaveConflicts(
            $userId,
            $startDate,
            $endDate,
            $excludeRequestId
        );
        
        return Json::item([
            'has_conflicts' => !empty($conflicts),
            'conflicts' => $conflicts
        ], message: 'Leave conflicts checked successfully');
    }

    /**
     * Get leave balance
     */
    public function getBalance(Request $request): JsonResponse
    {
        $userId = $request->input('user_id', $request->user()->id);
        $leaveTypeId = $request->input('leave_type_id');
        $year = $request->input('year', now()->year);
        
        $balance = $this->leaveRequestService->getLeaveBalance($userId, $leaveTypeId, $year);
        
        return Json::item($balance, message: 'Leave balance retrieved successfully');
    }
}

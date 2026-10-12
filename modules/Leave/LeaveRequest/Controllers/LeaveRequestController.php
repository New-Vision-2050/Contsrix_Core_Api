<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Modules\Leave\LeaveRequest\Presenters\LeaveRequestPresenter;
use Modules\Leave\LeaveRequest\Requests\CreateLeaveRequestRequest;
use Modules\Leave\LeaveRequest\Requests\GetLeaveRequestsRequest;
use Modules\Leave\LeaveRequest\Requests\RejectLeaveRequestRequest;
use Modules\Leave\LeaveRequest\Services\LeaveRequestService;
use Modules\User\Models\User;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    /**
     * Create a leave request (mobile employee form) and start its approval workflow.
     */
    public function store(CreateLeaveRequestRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $leaveRequest = $this->leaveRequestService->create($user, $request->createLeaveRequestDTO());

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request created successfully'
        );
    }

    /**
     * The authenticated employee's own leave requests (mobile).
     */
    public function myRequests(GetLeaveRequestsRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $paginator = $this->leaveRequestService->myRequests((string) $user->id, $request->filters());

        return Json::items(
            LeaveRequestPresenter::collection($paginator->items()),
            paginationSettings: $this->paginationSettings($paginator),
            message: 'Your leave requests retrieved successfully'
        );
    }

    /**
     * Admin list of all company leave requests.
     */
    public function index(GetLeaveRequestsRequest $request): JsonResponse
    {
        $paginator = $this->leaveRequestService->listForAdmin($request->filters());

        return Json::items(
            LeaveRequestPresenter::collection($paginator->items()),
            paginationSettings: $this->paginationSettings($paginator),
            message: 'Leave requests retrieved successfully'
        );
    }

    public function show(string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->getById($id);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request retrieved successfully'
        );
    }

    /**
     * Cancel a pending leave request (owner only).
     */
    public function cancel(string $id): JsonResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User, 401);

        $leaveRequest = $this->leaveRequestService->cancel($id, $user);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request cancelled successfully'
        );
    }

    /**
     * Approve the pending workflow step assigned to the current user.
     */
    public function approve(string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->approve($id);

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request approved successfully'
        );
    }

    /**
     * Reject the pending workflow step assigned to the current user.
     */
    public function reject(RejectLeaveRequestRequest $request, string $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestService->reject($id, $request->reason());

        return Json::item(
            (new LeaveRequestPresenter($leaveRequest))->getData(),
            message: 'Leave request rejected successfully'
        );
    }

    private function paginationSettings(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'next_page' => $paginator->currentPage() < $paginator->lastPage()
                ? $paginator->currentPage() + 1
                : $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'result_count' => $paginator->total(),
        ];
    }
}

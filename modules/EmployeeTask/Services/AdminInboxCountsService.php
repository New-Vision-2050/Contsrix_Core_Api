<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Modules\Advance\AdvanceRequest\Services\AdvanceRequestService;
use Modules\Leave\LeaveRequest\Services\LeaveRequestService;

/**
 * Unified admin-inbox counters: every request type that appears in
 * admin/employee-tasks/inbox (task requests, extensions, completion approvals,
 * end/start requests, leave requests, advance requests) counted with the same
 * per-approver authorization semantics (pending step assigned to the user or
 * listing them in authorized_user_ids).
 *
 * Single source of truth for the InboxCountsUpdated broadcast so the pushed
 * badge `total` always matches what the inbox endpoint would return,
 * regardless of which module's workflow step fired the event.
 */
final class AdminInboxCountsService
{
    public function __construct(
        private readonly EmployeeTaskRequestService   $requestService,
        private readonly EmployeeTaskExtensionService $extensionService,
        private readonly LeaveRequestService          $leaveRequestService,
        private readonly AdvanceRequestService        $advanceRequestService,
    ) {}

    /**
     * @return array{
     *     pending_tasks:int, pending_extensions:int, pending_approvals:int,
     *     pending_end_requests:int, pending_start_requests:int,
     *     pending_leave_requests:int, pending_advance_requests:int, total:int
     * }
     */
    public function forUser(string $userId, array $filters = []): array
    {
        $pendingTasks      = $this->requestService->inboxAll($userId, $filters)->count();
        $pendingExtensions = $this->extensionService->listInboxAllForAdmin($userId, $filters)->count();
        $pendingApprovals  = $this->requestService->inboxAllApprovals($userId, $filters)->count();
        $pendingEnd        = $this->requestService->inboxAllEndRequests($userId, $filters)->count();
        $pendingStart      = $this->requestService->inboxAllStartRequests($userId, $filters)->count();
        $pendingLeave      = $this->leaveRequestService->inboxForApprover($userId, $filters)->count();
        $pendingAdvance    = $this->advanceRequestService->inboxForApprover($userId, $filters)->count();

        return [
            'pending_tasks'            => $pendingTasks,
            'pending_extensions'       => $pendingExtensions,
            'pending_approvals'        => $pendingApprovals,
            'pending_end_requests'     => $pendingEnd,
            'pending_start_requests'   => $pendingStart,
            'pending_leave_requests'   => $pendingLeave,
            'pending_advance_requests' => $pendingAdvance,
            'total'                    => $pendingTasks + $pendingExtensions + $pendingApprovals
                + $pendingEnd + $pendingStart + $pendingLeave + $pendingAdvance,
        ];
    }
}

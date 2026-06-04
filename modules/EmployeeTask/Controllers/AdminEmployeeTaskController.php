<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Presenters\EmployeeTaskApprovalPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Presenters\InboxItemPresenter;
use Modules\EmployeeTask\Requests\AdminCancelTaskRequest;
use Modules\EmployeeTask\Requests\ApproveExtensionRequest;
use Modules\EmployeeTask\Requests\RejectExtensionRequest;
use Modules\EmployeeTask\Requests\RejectTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionWorkflowService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;

class AdminEmployeeTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeTaskRequestService         $requestService,
        private readonly EmployeeTaskExtensionService       $extensionService,
        private readonly EmployeeTaskExtensionWorkflowService $extensionWorkflow,
        private readonly EmployeeTaskApprovalService        $approvalService,
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['user_id', 'status', 'task_date', 'date_from', 'date_to']);
        $perPage = (int) request()->input('per_page', 15);

        $paginator = $this->requestService->adminList($filters, $perPage);

        return Json::items(
            mainItems: EmployeeTaskRequestPresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Task requests retrieved successfully',
        );
    }

    /**
     * Combined inbox for admin — returns task_request, extension_request, and task_approval
     * items all in the same unified shape so the frontend never needs to branch per type.
     */
    public function inbox(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['task_id', 'task_date', 'date_from', 'date_to']);
        $perPage = (int) request()->input('per_page', 15);
        $page    = (int) request()->input('page', 1);

        $taskItems     = $this->requestService->inboxAll($adminId, $filters);
        $extItems      = $this->extensionService->listInboxAllForAdmin($adminId, $filters);
        $approvalItems = $this->requestService->inboxAllApprovals($adminId, $filters);

        $combined = collect()
            ->merge($taskItems->map(fn ($t) => ['_type' => 'task_request',     '_model' => $t, '_at' => $t->created_at]))
            ->merge($extItems->map(fn ($e)  => ['_type' => 'extension_request','_model' => $e, '_at' => $e->created_at]))
            ->merge($approvalItems->map(fn ($a) => ['_type' => 'task_approval','_model' => $a, '_at' => $a->created_at]))
            ->sortByDesc('_at')
            ->values();

        $total = $combined->count();
        $slice = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $slice->map(function (array $entry): array {
            return match ($entry['_type']) {
                'task_request'     => InboxItemPresenter::fromTaskRequest($entry['_model']),
                'extension_request' => InboxItemPresenter::fromExtensionRequest($entry['_model']),
                'task_approval'    => InboxItemPresenter::fromApprovalRequest($entry['_model']),
            };
        })->all();

        return Json::items(
            mainItems: $items,
            paginationSettings: [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
            message: 'Inbox retrieved successfully',
        );
    }

    /**
     * Approve a task request, extension request, or task-approval request.
     * The type is resolved by trying each model in order.
     */
    public function approve(string $id): JsonResponse
    {
        try {
            if ($approval = EmployeeTaskApprovalRequest::find($id)) {
                $approval = $this->approvalService->approve(
                    $id,
                    (string) Auth::id(),
                    request()->input('approval_notes'),
                );
                return Json::item(EmployeeTaskApprovalPresenter::single($approval), message: 'Task approval request approved successfully');
            }

            if (EmployeeTaskExtensionRequest::find($id)) {
                $extension = $this->extensionWorkflow->approve(
                    $id,
                    (string) Auth::id(),
                    request()->input('approval_notes'),
                );
                return Json::item(EmployeeTaskExtensionPresenter::single($extension), message: 'Extension approved successfully');
            }

            $task = $this->requestService->approve($id, (string) Auth::id());
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task approved successfully');
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * Reject a task request, extension request, or task-approval request.
     */
    public function reject(RejectTaskRequest $request, string $id): JsonResponse
    {
        try {
            if ($approval = EmployeeTaskApprovalRequest::find($id)) {
                $approval = $this->approvalService->reject(
                    $id,
                    (string) Auth::id(),
                    $request->input('rejection_reason'),
                );
                return Json::item(EmployeeTaskApprovalPresenter::single($approval), message: 'Task approval request rejected successfully');
            }

            if (EmployeeTaskExtensionRequest::find($id)) {
                $extension = $this->extensionWorkflow->reject(
                    $id,
                    (string) Auth::id(),
                    $request->input('rejection_reason'),
                );
                return Json::item(EmployeeTaskExtensionPresenter::single($extension), message: 'Extension rejected successfully');
            }

            $task = $this->requestService->reject(
                $id,
                (string) Auth::id(),
                $request->input('rejection_reason'),
            );
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task rejected successfully');
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function destroy(AdminCancelTaskRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->requestService->cancelByAdmin(
                $id,
                (string) Auth::id(),
                $request->input('cancellation_reason'),
            );
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task cancelled successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function extensionRequests(): JsonResponse
    {
        $filters = request()->only(['task_id', 'date_from', 'date_to']);
        $perPage = (int) request()->input('per_page', 15);

        $paginator = $this->extensionService->listInboxForAdmin(
            (string) Auth::id(),
            $filters,
            $perPage,
        );

        return Json::items(
            mainItems: EmployeeTaskExtensionPresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Extension requests inbox retrieved successfully',
        );
    }

    public function approveExtension(ApproveExtensionRequest $request, string $extensionId): JsonResponse
    {
        try {
            $extension = $this->extensionWorkflow->approve(
                $extensionId,
                (string) Auth::id(),
                $request->input('approval_notes'),
            );

            return Json::item(
                EmployeeTaskExtensionPresenter::single($extension),
                message: 'Extension approved successfully',
            );
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function rejectExtension(RejectExtensionRequest $request, string $extensionId): JsonResponse
    {
        try {
            $extension = $this->extensionWorkflow->reject(
                $extensionId,
                (string) Auth::id(),
                $request->input('rejection_reason'),
            );

            return Json::item(
                EmployeeTaskExtensionPresenter::single($extension),
                message: 'Extension rejected successfully',
            );
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function inboxCounts(): JsonResponse
    {
        $adminId = (string) Auth::id();
        $filters = request()->only(['task_id', 'task_date', 'date_from', 'date_to']);

        $taskCount     = $this->requestService->inboxAll($adminId, $filters)->count();
        $extCount      = $this->extensionService->listInboxAllForAdmin($adminId, $filters)->count();
        $approvalCount = $this->requestService->inboxAllApprovals($adminId, $filters)->count();

        return Json::item([
            'pending_tasks'      => $taskCount,
            'pending_extensions' => $extCount,
            'pending_approvals'  => $approvalCount,
            'total'              => $taskCount + $extCount + $approvalCount,
        ], message: 'Inbox counts retrieved successfully');
    }
}

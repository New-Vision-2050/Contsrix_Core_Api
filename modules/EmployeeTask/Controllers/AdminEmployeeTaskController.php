<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest;
use Modules\EmployeeTask\Models\EmployeeTaskEndRequest;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Models\EmployeeTaskStartRequest;
use Modules\EmployeeTask\Presenters\EmployeeTaskApprovalPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Presenters\InboxItemPresenter;
use Modules\EmployeeTask\Requests\AdminCancelTaskRequest;
use Modules\EmployeeTask\Requests\ApproveExtensionRequest;
use Modules\EmployeeTask\Requests\RejectExtensionRequest;
use Modules\EmployeeTask\Requests\RejectTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskEndRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionWorkflowService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\EmployeeTask\Services\EmployeeTaskStartRequestService;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\ProcedureSetting\Services\ActionTakerResolver;
use Modules\User\Models\User;

class AdminEmployeeTaskController extends Controller
{
    /**
     * action_taker_type values that are resolved dynamically per task context
     * (e.g. management hierarchy of the task owner) instead of via the static
     * procedure_setting_step_action_takers table.
     */
    private const DYNAMIC_ACTION_TAKER_TYPES = [
        'management_hierarchy',
        'specific_procedures',
        'receiver_company',
        'himself',
        'assigned_user',
    ];

    public function __construct(
        private readonly EmployeeTaskRequestService           $requestService,
        private readonly EmployeeTaskExtensionService         $extensionService,
        private readonly EmployeeTaskExtensionWorkflowService $extensionWorkflow,
        private readonly EmployeeTaskApprovalService          $approvalService,
        private readonly EmployeeTaskEndRequestService        $endRequestService,
        private readonly EmployeeTaskStartRequestService      $startRequestService,
        private readonly ActionTakerResolver                  $actionTakerResolver,
    ) {}

    /**
     * For inbox items whose current_procedure_step uses a dynamic
     * action-taker type (management_hierarchy, specific_procedures,
     * receiver_company, himself, assigned_user), the static
     * `currentProcedureStep.actionTakers` table is empty, so the repository's
     * SQL-level filter treats the step as "open" and lets every admin see it.
     *
     * This re-filters those items down to the admins actually resolved for
     * the step (given the task owner + context), and annotates each model
     * with a `resolved_action_takers` attribute (with names) so the
     * presenter can display the real action-takers instead of nulls.
     */
    private function applyDynamicActionTakerResolution(\Illuminate\Support\Collection $items, string $adminId): \Illuminate\Support\Collection
    {
        return $items->filter(function ($model) use ($adminId) {
            if (!$model->relationLoaded('currentProcedureStep') || !$model->currentProcedureStep) {
                return true;
            }

            $step = $model->currentProcedureStep;
            $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

            if (!in_array($actionTakerType, self::DYNAMIC_ACTION_TAKER_TYPES, true)) {
                return true;
            }

            $task = $model->relationLoaded('task') ? $model->task : null;
            $context = $task && $task->project_id ? ['project_id' => $task->project_id] : [];
            $createdByUserId = $task->user_id ?? null;

            $resolvedIds = $this->actionTakerResolver->resolveUsersForStep($step, $createdByUserId, $context);

            $names = $resolvedIds === []
                ? collect()
                : User::query()->whereIn('id', $resolvedIds)->pluck('name', 'id');

            $model->setAttribute('resolved_action_takers', collect($resolvedIds)->map(fn ($id) => [
                'user_id' => $id,
                'name'    => $names[$id] ?? null,
            ])->all());

            return in_array($adminId, $resolvedIds, true);
        })->values();
    }

    /**
     * Pending tasks where the admin is an action-taker in the current
     * process step. Shared by inbox() and inboxCounts() so the two
     * endpoints never drift out of sync.
     */
    private function getInboxTaskItems(string $adminId, array $filters): \Illuminate\Support\Collection
    {
        return $this->requestService->inboxAll($adminId, $filters)
            ->values();
    }

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
        $filters = request()->only(['task_id', 'task_date', 'date_from', 'date_to', 'type', 'duration_from', 'duration_to']);
        $perPage = (int) request()->input('per_page', 15);
        $page    = (int) request()->input('page', 1);
        $sort    = request()->input('sort', 'created_at_desc');

        $type = $filters['type'] ?? null;

        $taskItems         = $this->getInboxTaskItems($adminId, $filters);
        $extItems          = $this->applyDynamicActionTakerResolution(
            $this->extensionService->listInboxAllForAdmin($adminId, $filters),
            $adminId,
        );
        $approvalItems     = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllApprovals($adminId, $filters),
            $adminId,
        );
        $endRequestItems   = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllEndRequests($adminId, $filters),
            $adminId,
        );
        $startRequestItems = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllStartRequests($adminId, $filters),
            $adminId,
        );

        $combined = collect()
            ->merge($taskItems->map(fn ($t)  => ['_type' => 'task_request',      '_model' => $t, '_at' => $t->created_at]))
            ->merge($extItems->map(fn ($e)   => ['_type' => 'extension_request', '_model' => $e, '_at' => $e->created_at]))
            ->merge($approvalItems->map(fn ($a) => ['_type' => 'task_approval',  '_model' => $a, '_at' => $a->created_at]))
            ->merge($endRequestItems->map(fn ($r) => ['_type' => 'end_request',  '_model' => $r, '_at' => $r->created_at]))
            ->merge($startRequestItems->map(fn ($r) => ['_type' => 'start_request', '_model' => $r, '_at' => $r->created_at]));

        if ($type !== null) {
            $combined = $combined->filter(fn ($item) => $item['_type'] === $type);
        }

        $combined = $combined->sortByDesc('_at')->values();

        $direction = str_ends_with($sort, '_desc') ? 'desc' : 'asc';
        $column    = str_replace(['_desc', '_asc'], '', $sort);

        $combined = $combined->sortBy(function ($item) use ($column) {
            $model = $item['_model'];
            return match ($column) {
                'task_date'      => $model->task_date ?? ($model->task->task_date ?? null),
                'duration_hours' => $model->duration_hours ?? ($model->task->duration_hours ?? 0),
                'title'          => $model->title ?? ($model->task->title ?? ''),
                'status'         => $model->status,
                default          => $item['_at'],
            };
        }, SORT_REGULAR, $direction === 'desc')->values();

        $total = $combined->count();
        $slice = $combined->slice(($page - 1) * $perPage, $perPage)->values();

        $items = $slice->map(function (array $entry): array {
            return match ($entry['_type']) {
                'task_request'     => InboxItemPresenter::fromTaskRequest($entry['_model']),
                'extension_request' => InboxItemPresenter::fromExtensionRequest($entry['_model']),
                'task_approval'    => InboxItemPresenter::fromApprovalRequest($entry['_model']),
                'end_request'      => InboxItemPresenter::fromEndRequest($entry['_model']),
                'start_request'    => InboxItemPresenter::fromStartRequest($entry['_model']),
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
     * Inbox for tasks that are assigned to the current user (user_id = current user).
     * Returned in the same unified shape as the admin inbox, with type = 'assigned_task'.
     */
    public function assignedInbox(): JsonResponse
    {
        $userId  = (string) Auth::id();
        $filters = request()->only(['task_id', 'status', 'task_date', 'date_from', 'date_to', 'duration_from', 'duration_to']);
        $perPage = (int) request()->input('per_page', 15);
        $page    = (int) request()->input('page', 1);
        $sort    = request()->input('sort', 'created_at_desc');

        $items = $this->requestService->assignedInbox($userId, $filters)
            ->sortByDesc('created_at')
            ->values();

        $direction = str_ends_with($sort, '_desc') ? 'desc' : 'asc';
        $column    = str_replace(['_desc', '_asc'], '', $sort);

        $items = $items->sortBy(function ($task) use ($column) {
            return match ($column) {
                'task_date'      => $task->task_date,
                'duration_hours' => $task->duration_hours ?? 0,
                'title'          => $task->title,
                'status'         => $task->status,
                default          => $task->created_at,
            };
        }, SORT_REGULAR, $direction === 'desc')->values();

        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $payload = $slice->map(function ($task) {
            $item = InboxItemPresenter::fromTaskRequest($task);
            $item['type'] = 'assigned_task';
            $item['type_label'] = app()->getLocale() === 'ar' ? 'مهمة مسندة' : 'Assigned Task';

            return $item;
        })->all();

        return Json::items(
            mainItems: $payload,
            paginationSettings: [
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
                'per_page'     => $perPage,
                'total'        => $total,
            ],
            message: 'Assigned inbox retrieved successfully',
        );
    }

    /**
     * Approve a task request, extension request, or task-approval request.
     * The type is resolved by trying each model in order.
     */
    public function approve(string $id): JsonResponse
    {
        try {
            if (EmployeeTaskApprovalRequest::find($id)) {
                $approval = $this->approvalService->approve(
                    $id,
                    (string) Auth::id(),
                    request()->input('approval_notes'),
                );
                return Json::item(EmployeeTaskApprovalPresenter::single($approval), message: 'Task approval request approved successfully');
            }

            if (EmployeeTaskEndRequest::find($id)) {
                $endRequest = $this->endRequestService->approve(
                    $id,
                    (string) Auth::id(),
                    request()->input('approval_notes'),
                );
                return Json::item(InboxItemPresenter::fromEndRequest($endRequest->load(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])), message: 'End request approved successfully');
            }

            if (EmployeeTaskStartRequest::find($id)) {
                $startRequest = $this->startRequestService->approve(
                    $id,
                    (string) Auth::id(),
                    request()->input('approval_notes'),
                );
                return Json::item(InboxItemPresenter::fromStartRequest($startRequest->load(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])), message: 'Start request approved successfully');
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
            if (EmployeeTaskApprovalRequest::find($id)) {
                $approval = $this->approvalService->reject(
                    $id,
                    (string) Auth::id(),
                    $request->input('rejection_reason'),
                );
                return Json::item(EmployeeTaskApprovalPresenter::single($approval), message: 'Task approval request rejected successfully');
            }

            if (EmployeeTaskEndRequest::find($id)) {
                $endRequest = $this->endRequestService->reject(
                    $id,
                    (string) Auth::id(),
                    $request->input('rejection_reason'),
                );
                return Json::item(InboxItemPresenter::fromEndRequest($endRequest->load(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])), message: 'End request rejected successfully');
            }

            if (EmployeeTaskStartRequest::find($id)) {
                $startRequest = $this->startRequestService->reject(
                    $id,
                    (string) Auth::id(),
                    $request->input('rejection_reason'),
                );
                return Json::item(InboxItemPresenter::fromStartRequest($startRequest->load(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user'])), message: 'Start request rejected successfully');
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

        $taskCount         = $this->getInboxTaskItems($adminId, $filters)->count();
        $extCount          = $this->applyDynamicActionTakerResolution(
            $this->extensionService->listInboxAllForAdmin($adminId, $filters),
            $adminId,
        )->count();
        $approvalCount     = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllApprovals($adminId, $filters),
            $adminId,
        )->count();
        $endRequestCount   = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllEndRequests($adminId, $filters),
            $adminId,
        )->count();
        $startRequestCount = $this->applyDynamicActionTakerResolution(
            $this->requestService->inboxAllStartRequests($adminId, $filters),
            $adminId,
        )->count();

        return Json::item([
            'pending_tasks'          => $taskCount,
            'pending_extensions'     => $extCount,
            'pending_approvals'      => $approvalCount,
            'pending_end_requests'   => $endRequestCount,
            'pending_start_requests' => $startRequestCount,
            'total'                  => $taskCount + $extCount + $approvalCount + $endRequestCount + $startRequestCount,
        ], message: 'Inbox counts retrieved successfully');
    }
}

<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\DTO\ApproveExtensionRequestDTO;
use Modules\EmployeeTask\DTO\RejectExtensionRequestDTO;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Requests\AdminCancelTaskRequest;
use Modules\EmployeeTask\Requests\ApproveExtensionRequest;
use Modules\EmployeeTask\Requests\RejectExtensionRequest;
use Modules\EmployeeTask\Requests\RejectTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionResolveService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;

class AdminEmployeeTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeTaskRequestService $requestService,
        private readonly EmployeeTaskExtensionResolveService $extensionResolveService,
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

    public function inbox(): JsonResponse
    {
        $filters = request()->only(['task_date', 'date_from', 'date_to']);
        $perPage = (int) request()->input('per_page', 15);

        $paginator = $this->requestService->inbox((string) Auth::id(), $filters, $perPage);

        return Json::items(
            mainItems: EmployeeTaskRequestPresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Inbox retrieved successfully',
        );
    }

    public function approve(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->approve($id, (string) Auth::id());
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task approved successfully');
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function reject(RejectTaskRequest $request, string $id): JsonResponse
    {
        try {
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
        $perPage = (int) request()->input('per_page', 15);
        $paginator = $this->extensionResolveService->listPending($perPage);

        return Json::items(
            mainItems: EmployeeTaskExtensionPresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Pending extension requests retrieved successfully',
        );
    }

    public function approveExtension(ApproveExtensionRequest $request, string $extensionId): JsonResponse
    {
        try {
            $dto = new ApproveExtensionRequestDTO(
                extensionId: $extensionId,
                adminId: (string) Auth::id(),
                approvalNotes: $request->input('approval_notes'),
            );

            $extension = $this->extensionResolveService->approve($dto);

            return Json::item(
                EmployeeTaskExtensionPresenter::single($extension),
                message: 'Extension approved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function rejectExtension(RejectExtensionRequest $request, string $extensionId): JsonResponse
    {
        try {
            $dto = new RejectExtensionRequestDTO(
                extensionId: $extensionId,
                adminId: (string) Auth::id(),
                rejectionReason: $request->input('rejection_reason'),
            );

            $extension = $this->extensionResolveService->reject($dto);

            return Json::item(
                EmployeeTaskExtensionPresenter::single($extension),
                message: 'Extension rejected successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }
}

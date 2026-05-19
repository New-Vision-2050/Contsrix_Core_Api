<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Requests\AdminCancelTaskRequest;
use Modules\EmployeeTask\Requests\RejectTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;

class AdminEmployeeTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeTaskRequestService  $requestService,
        private readonly EmployeeTaskExtensionService $extensionService,
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
        $extensions = \Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->with(['task', 'requestedByUser'])
            ->orderByDesc('created_at')
            ->paginate((int) request()->input('per_page', 15));

        return Json::items(
            mainItems: EmployeeTaskExtensionPresenter::collection($extensions->items()),
            paginationSettings: [
                'current_page' => $extensions->currentPage(),
                'last_page'    => $extensions->lastPage(),
                'per_page'     => $extensions->perPage(),
                'total'        => $extensions->total(),
            ],
            message: 'Pending extension requests retrieved successfully',
        );
    }

    public function approveExtension(string $extensionId): JsonResponse
    {
        try {
            $extension = $this->extensionService->approveExtension($extensionId, (string) Auth::id());
            return Json::item(
                (new EmployeeTaskExtensionPresenter($extension))->toArray(),
                message: 'Extension approved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function rejectExtension(string $extensionId): JsonResponse
    {
        try {
            request()->validate(['review_notes' => ['nullable', 'string', 'max:1000']]);

            $extension = $this->extensionService->rejectExtension(
                $extensionId,
                (string) Auth::id(),
                request()->input('review_notes'),
            );

            return Json::item(
                (new EmployeeTaskExtensionPresenter($extension))->toArray(),
                message: 'Extension rejected successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }
}

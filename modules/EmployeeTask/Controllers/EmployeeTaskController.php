<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use BasePackage\Shared\Presenters\Json;
use Modules\EmployeeTask\DTO\CreateEmployeeTaskRequestDTO;
use Modules\EmployeeTask\DTO\EndTaskDTO;
use Modules\EmployeeTask\DTO\StartTaskDTO;
use Modules\EmployeeTask\DTO\CreateExtensionRequestDTO;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskSessionPresenter;
use Modules\EmployeeTask\Requests\CreateEmployeeTaskRequest;
use Modules\EmployeeTask\Requests\CreateExtensionRequest;
use Modules\EmployeeTask\Requests\EndTaskRequest;
use Modules\EmployeeTask\Requests\LocationPingRequest;
use Modules\EmployeeTask\Requests\StartTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\User\Models\User;

class EmployeeTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeTaskRequestService  $requestService,
        private readonly EmployeeTaskLifecycleService $lifecycleService,
        private readonly EmployeeTaskLocationService  $locationService,
        private readonly EmployeeTaskExtensionService $extensionService,
    ) {}

    public function index(): JsonResponse
    {
        $filters  = request()->only(['status', 'task_date', 'date_from', 'date_to']);
        $perPage  = (int) request()->input('per_page', 15);
        $userId   = (string) Auth::id();

        $paginator = $this->requestService->list($userId, $filters, $perPage);

        return Json::items(
            mainItems:          EmployeeTaskRequestPresenter::collection($paginator->items()),
            paginationSettings: [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            message: 'Task requests retrieved successfully',
        );
    }

    public function store(CreateEmployeeTaskRequest $request): JsonResponse
    {
        try {
            $dto = new CreateEmployeeTaskRequestDTO(
                userId:                  (string) Auth::id(),
                title:                   $request->input('title'),
                durationHours:           (float) $request->input('duration_hours'),
                taskDate:                $request->input('task_date'),
                taskLatitude:            (float) $request->input('task_latitude'),
                taskLongitude:           (float) $request->input('task_longitude'),
                description:             $request->input('description'),
                projectId:               $request->input('project_id'),
                approvalResponsibleId:   $request->input('approval_responsible_id'),
                assignmentResponsibleId: $request->input('assignment_responsible_id'),
                notes:                   $request->input('notes'),
            );

            $task = $this->requestService->create($dto);

            return Json::item(
                EmployeeTaskRequestPresenter::single($task->load(['sessions'])),
                message: 'Task request submitted successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->get($id);
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task retrieved successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->cancelByEmployee($id, (string) Auth::id());
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task request cancelled successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function start(StartTaskRequest $request, string $id): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::user();
            $user->load(['userProfessionalData.branch.address.country', 'userProfessionalData.attendanceConstraint']);

            $task = $this->lifecycleService->start(
                $id,
                new StartTaskDTO(
                    latitude:  (float) $request->input('latitude'),
                    longitude: (float) $request->input('longitude'),
                ),
                $user,
            );

            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task started successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function pause(string $id): JsonResponse
    {
        try {
            $task = $this->lifecycleService->pause($id, (string) Auth::id());
            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task paused successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function resume(string $id): JsonResponse
    {
        try {
            request()->validate([
                'latitude'  => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            $task = $this->lifecycleService->resume(
                $id,
                (float) request()->input('latitude'),
                (float) request()->input('longitude'),
            );

            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task resumed successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function end(EndTaskRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->lifecycleService->end(
                $id,
                new EndTaskDTO(
                    latitude:  (float) $request->input('latitude'),
                    longitude: (float) $request->input('longitude'),
                    notes:     $request->input('notes'),
                ),
            );

            return Json::item(EmployeeTaskRequestPresenter::single($task), message: 'Task ended successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function liveStatus(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->get($id);
            $task->load(['sessions']);

            $presenter = new EmployeeTaskRequestPresenter($task);

            return Json::item(
                array_merge(
                    EmployeeTaskRequestPresenter::single($task),
                    $presenter->liveStatus(),
                ),
                message: 'Live status retrieved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function locationPing(LocationPingRequest $request, string $id): JsonResponse
    {
        try {
            $task = $this->requestService->get($id);

            /** @var User $user */
            $user = Auth::user();
            $user->loadMissing(['userProfessionalData.attendanceConstraint']);

            $threshold = $this->locationService->outOfRadiusThresholdMinutes($user);

            $result = $this->locationService->processLocationPing(
                $task,
                (float) $request->input('latitude'),
                (float) $request->input('longitude'),
                $request->input('timestamp'),
                $threshold,
            );

            return Json::item($result, message: 'Location ping processed');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function checkLocation(string $id): JsonResponse
    {
        try {
            request()->validate([
                'latitude'  => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ]);

            $task       = $this->requestService->get($id);
            $inLocation = $this->locationService->isWithinTaskRadius(
                $task,
                (float) request()->input('latitude'),
                (float) request()->input('longitude'),
            );

            return Json::item([
                'in_location'   => $inLocation,
                'radius_meters' => $task->radius_meters,
            ], message: 'Location checked successfully');
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function sessions(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->get($id);
            return Json::items(
                EmployeeTaskSessionPresenter::collection($task->sessions),
                message: 'Sessions retrieved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function storeExtension(CreateExtensionRequest $request, string $id): JsonResponse
    {
        try {
            $dto = new CreateExtensionRequestDTO(
                taskId:          $id,
                requestedBy:     (string) Auth::id(),
                additionalHours: (float) $request->input('additional_hours'),
                reason:          $request->input('reason'),
            );

            $extension = $this->extensionService->requestExtension($dto);

            return Json::item(
                (new EmployeeTaskExtensionPresenter($extension))->toArray(),
                message: 'Extension request submitted successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    public function listExtensions(string $id): JsonResponse
    {
        try {
            $extensions = $this->extensionService->listExtensions($id);
            return Json::items(
                EmployeeTaskExtensionPresenter::collection($extensions),
                message: 'Extension requests retrieved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }
}

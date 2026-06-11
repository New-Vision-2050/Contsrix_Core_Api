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
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Presenters\EmployeeTaskApprovalPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskExtensionPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskRequestPresenter;
use Modules\EmployeeTask\Presenters\EmployeeTaskSessionPresenter;
use Modules\EmployeeTask\Requests\CreateEmployeeTaskRequest;
use Modules\EmployeeTask\Requests\CreateExtensionRequest;
use Modules\EmployeeTask\Requests\EndTaskRequest;
use Modules\EmployeeTask\Requests\LocationPingRequest;
use Modules\EmployeeTask\Requests\StartTaskRequest;
use Modules\EmployeeTask\Services\EmployeeTaskApprovalService;
use Modules\EmployeeTask\Services\EmployeeTaskExtensionService;
use Modules\EmployeeTask\Services\EmployeeTaskLifecycleService;
use Modules\EmployeeTask\Services\EmployeeTaskLocationService;
use Modules\EmployeeTask\Services\EmployeeTaskRequestService;
use Modules\ProcedureSetting\Exceptions\ProcedureWorkflowException;
use Modules\User\Models\User;

class EmployeeTaskController extends Controller
{
    public function __construct(
        private readonly EmployeeTaskRequestService   $requestService,
        private readonly EmployeeTaskLifecycleService  $lifecycleService,
        private readonly EmployeeTaskLocationService   $locationService,
        private readonly EmployeeTaskExtensionService  $extensionService,
        private readonly EmployeeTaskApprovalService   $approvalService,
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only([
            'status',
            'project_id',
            'task_date',
            'date_from',
            'date_to',
            'search',
        ]);
        $perPage = (int) request()->input('per_page', 15);
        $sort    = request()->input('sort');
        $userId  = (string) Auth::id();

        $paginator = $this->requestService->list($userId, $filters, $perPage, $sort);

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

    /**
     * GET /employee-tasks/filters
     *
     * Returns filter metadata with counts for the mobile filter UI:
     *   - statuses: key, title_ar, title_en, count
     *   - projects: key (project_id), title, count
     *   - duration: min_minutes, max_minutes
     */
    public function filters(): JsonResponse
    {
        $userId   = (string) Auth::id();
        $locale   = app()->getLocale();
        $metadata = $this->requestService->getFilterMetadata($userId);

        $statuses = [];
        foreach ($metadata['status_counts'] as $statusValue => $count) {
            try {
                $enum = EmployeeTaskStatus::from($statusValue);
            } catch (\ValueError) {
                continue;
            }
            $statuses[] = [
                'key'      => $statusValue,
                'title_ar' => $enum->label('ar'),
                'title_en' => $enum->label('en'),
                'count'    => (int) $count,
            ];
        }

        $projects = [];
        foreach ($metadata['project_counts'] as $project) {
            $projects[] = [
                'key'   => $project['id'],
                'title' => $project['name'],
                'count' => $project['count'],
            ];
        }

        $duration = [
            'key'       => 'duration_minutes',
            'title_ar'  => 'مدة المهمة',
            'title_en'  => 'Task Duration',
            'min_minutes' => $metadata['duration']['min_hours'] !== null
                ? (int) round($metadata['duration']['min_hours'] * 60)
                : null,
            'max_minutes' => $metadata['duration']['max_hours'] !== null
                ? (int) round($metadata['duration']['max_hours'] * 60)
                : null,
        ];

        return Json::item([
            'statuses' => $statuses,
            'projects' => $projects,
            'duration' => $duration,
        ], message: 'Filter metadata retrieved successfully');
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
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
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

    /**
     * GET /employee-tasks/{id}/status
     *
     * Returns the 3-step pipeline the mobile app displays:
     *   1. قبول   — Initial task acceptance by admin
     *   2. تأكيد الموقع — GPS location confirmed via location-ping
     *   3. اعتماد — Final task-completion approval by admin
     *
     * Each step has: key, label_ar, label_en, status (pending|completed), badge, completed_at
     */
    public function liveStatus(string $id): JsonResponse
    {
        try {
            $task = $this->requestService->get($id);
            $task->load(['sessions']);

            $presenter = new EmployeeTaskRequestPresenter($task);
            $locale    = app()->getLocale();

            $pipeline = $this->buildStatusPipeline($task, $locale);

            return Json::item(
                array_merge(
                    EmployeeTaskRequestPresenter::single($task),
                    $presenter->liveStatus(),
                    ['pipeline' => $pipeline],
                ),
                message: 'Live status retrieved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    /**
     * POST /employee-tasks/{id}/location-ping
     *
     * Processes a GPS ping. If the employee is in location for the first time,
     * records location_confirmed_at on the task.
     */
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

            // Record the first time location is confirmed so the status pipeline
            // can show "تأكيد الموقع" as completed.
            if ($result['in_location'] && !$task->location_confirmed_at) {
                $task->update(['location_confirmed_at' => now()]);
            }

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

    /**
     * POST /employee-tasks/{id}/request-approval  (multipart/form-data)
     *
     * Employee submits the task for final admin approval (ارسال للاعتماد).
     * Accepts an optional file upload under the key `file` (single file)
     * or `files[]` (multiple files). Uses the project's FileUploadService
     * and Spatie Media Library — same pattern as ClientRequest attachments.
     */
    public function requestApproval(string $id): JsonResponse
    {
        try {
            request()->validate([
                'notes' => ['nullable', 'string', 'max:2000'],
                'file'  => ['nullable', 'file', 'max:20480'],
            ]);

            $uploadedFiles = request()->hasFile('file') ? request()->file('file') : null;

            $approval = $this->approvalService->create(
                taskId: $id,
                userId: (string) Auth::id(),
                notes:  request()->input('notes'),
                file:   $uploadedFiles,
            );

            $approval->load(['task.user', 'requestedByUser', 'currentProcedureStep.actionTakers.user', 'media']);

            return Json::item(
                EmployeeTaskApprovalPresenter::single($approval),
                message: 'Task approval request submitted successfully',
            );
        } catch (EmployeeTaskException | ProcedureWorkflowException $e) {
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
            $extensions = $this->extensionService->listForTask($id);
            return Json::items(
                EmployeeTaskExtensionPresenter::collection($extensions),
                message: 'Extension requests retrieved successfully',
            );
        } catch (EmployeeTaskException $e) {
            return Json::error($e->getMessage(), $e->getCode() ?: 422);
        }
    }

    // ─── private helpers ─────────────────────────────────────────────────────

    /**
     * Builds the 3-step approval pipeline shown in the mobile UI (image 1):
     *
     *  [قبول] → [تأكيد الموقع] → [اعتماد]
     */
    private function buildStatusPipeline($task, string $locale): array
    {
        $ar = $locale === 'ar';

        // Step 1: Initial acceptance — task was approved (moved out of pending)
        $step1Done = !in_array($task->status, [
            EmployeeTaskStatus::Pending->value,
            EmployeeTaskStatus::Rejected->value,
            EmployeeTaskStatus::Cancelled->value,
        ], true);

        // Step 2: Location confirmed — employee was in range at least once
        $step2Done = $task->location_confirmed_at !== null;

        // Step 3: Final task approval — a task_approval request was approved
        $step3Done = $task->approvalRequests()
            ->where('status', 'approved')
            ->exists();

        $step3Pending = !$step3Done && $task->hasPendingApprovalRequest();

        return [
            [
                'key'          => 'acceptance',
                'label'        => $ar ? 'قبول' : 'Acceptance',
                'badge'        => $ar ? 'اعتماد' : 'Approved',
                'status'       => $step1Done ? 'completed' : 'pending',
                'completed_at' => $step1Done ? $task->approved_at?->format('Y-m-d H:i:s') : null,
            ],
            [
                'key'          => 'location_confirmation',
                'label'        => $ar ? 'تأكيد الموقع' : 'Location Confirmation',
                'badge'        => $ar ? 'تم تأكيد' : 'Confirmed',
                'status'       => $step2Done ? 'completed' : 'pending',
                'completed_at' => $step2Done ? $task->location_confirmed_at->format('Y-m-d H:i:s') : null,
            ],
            [
                'key'          => 'task_approval',
                'label'        => $ar ? 'اعتماد' : 'Task Approval',
                'badge'        => $ar ? 'اعتماد مهمة' : 'Task Approved',
                'status'       => $step3Done ? 'completed' : ($step3Pending ? 'pending_approval' : 'pending'),
                'completed_at' => null,
            ],
        ];
    }
}

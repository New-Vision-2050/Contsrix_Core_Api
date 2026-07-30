<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PermissionMiddleware;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Exports\ProcedureSettingExport;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingHandler;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\ProcedureSetting\Presenters\ProcedureSettingPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\ExportProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingListRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\ToggleBranchWorkFlowRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingCRUDService;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\Project\ProjectManagement\Presenters\ProjectProcedurePresenter;
use Modules\Project\ProjectManagement\Services\ProjectProcedureService;
use Modules\RoleAndPermission\Enums\Permission;
use Ramsey\Uuid\Uuid;

class ProcedureSettingController extends Controller
{
    public function __construct(
        private ProcedureSettingCRUDService $procedureSettingService,
        private UpdateProcedureSettingHandler $updateProcedureSettingHandler,
        private DeleteProcedureSettingHandler $deleteProcedureSettingHandler,
        private ProcedureWorkflowService $workflowService,
        private ProjectProcedureService $projectProcedureService,
        private PermissionMiddleware $permissionMiddleware,
    ) {}

    /**
     * GET /api/v1/procedure-settings/approval-responsibles?type=...
     *
     * Preview the action-takers of the first procedure step for the given
     * procedure type. Used by creation-form UIs that need to display
     * "مسؤل الاعتماد" before the entity is created.
     *
     * If `auto_approve` is true → no one needs to approve; the consuming
     * service should create the entity in its already-approved terminal state.
     */
    public function approvalResponsibles()
    {
        $type = (string) request()->query('type', '');
        $form = (string) request()->query('form', '');
        $branchId = (string) request()->query('branch_id', '');

        if ($type === '') {
            return Json::error(__('The type query parameter is required.'), 422);
        }

        $formKey = $form !== '' ? $form : null;
        $context = $branchId !== '' ? ['branch_id' => $branchId] : [];

        return Json::item(
            $this->workflowService->getApprovalResponsibles(
                $type,
                (string) Auth::id(),
                $context,
                $formKey
            ),
            message: 'Approval responsibles retrieved successfully',
        );
    }

    /**
     * GET /api/v1/procedure-settings/types
     */
    public function types(): JsonResponse
    {
        $types = array_map(
            static fn (ProcedureSettingType $type): array => $type->toDefinition(),
            ProcedureSettingType::cases(),
        );

        return Json::items(
            mainItems: $types,
            message: 'Procedure setting types retrieved successfully',
        );
    }

    public function index(GetProcedureSettingListRequest $request): JsonResponse
    {
        $filters = $request->getFilters();

        if ($filters === []) {
            $defaultWorkFlow = $this->procedureSettingService->getDefaultWorkFlowForList();

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow, $filters) : null);
        }

        if (isset($filters['type']) && ! isset($filters['branch_id']) && ! isset($filters['work_flow_id'])) {
            $defaultWorkFlow = $this->defaultWorkFlowForFilters($filters);

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow, $filters) : null);
        }

        if (isset($filters['branch_id'])) {
            $workFlow = $this->procedureSettingService->firstByWorkFlowFilters($filters);

            return Json::item($workFlow ? $this->presentWorkFlow($workFlow, $filters) : null);
        }

        $list = $this->procedureSettingService->listByWorkFlow($filters);

        return Json::items($list->map(fn (WorkFlow $workFlow): array => $this->presentWorkFlow($workFlow, $filters))->values()->all());
    }

    public function show(GetProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->projectIdFromRequest($request);

        if ($projectId !== null) {
            $this->authorizeProjectProcedure(Permission::PROJECT_MANAGEMENT_VIEW());

            $item = $this->projectProcedureService->get(
                $projectId,
                (string) $request->route('id'),
                $request->parentProcedureSettingId(),
            );

            return Json::item((new ProjectProcedurePresenter($item))->getData());
        }

        $item = $this->procedureSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->projectIdFromRequest($request);

        if ($projectId !== null) {
//            $this->authorizeProjectProcedure(Permission::PROJECT_MANAGEMENT_CREATE());

            $item = $this->projectProcedureService->create(
                $projectId,
                $request->projectProcedureData(),
                $request->projectProcedureMetadataData(),
                $request->parentProcedureSettingId(),
            );

            return Json::item((new ProjectProcedurePresenter($item))->getData());
        }

        $createdItem = $this->procedureSettingService->create($request->createCreateProcedureSettingDTO());

        $presenter = new ProcedureSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function toggleBranchWorkFlows(ToggleBranchWorkFlowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $workFlow = $this->procedureSettingService->toggleBranchDefaultWorkFlows(
            (int) $validated['branch_id'],
            (bool) $validated['checked'],
            (string) $validated['type'],
        );

        return Json::item($workFlow ? $this->presentWorkFlow($workFlow) : null);
    }

    public function update(UpdateProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->projectIdFromRequest($request);

        if ($projectId !== null) {
            $this->authorizeProjectProcedure(Permission::PROJECT_MANAGEMENT_UPDATE());

            $item = $this->projectProcedureService->update(
                $projectId,
                (string) $request->route('id'),
                $request->projectProcedureData(),
                $request->projectProcedureMetadataData(),
                $request->parentProcedureSettingId(),
            );

            return Json::item((new ProjectProcedurePresenter($item))->getData());
        }

        $command = $request->createUpdateProcedureSettingCommand();
        $this->updateProcedureSettingHandler->handle($command);

        $item = $this->procedureSettingService->get($command->getId());

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->projectIdFromRequest($request);

        if ($projectId !== null) {
            $this->authorizeProjectProcedure(Permission::PROJECT_MANAGEMENT_UPDATE());

            $this->projectProcedureService->delete(
                $projectId,
                (string) $request->route('id'),
                $request->parentProcedureSettingId(),
            );

            return Json::deleted();
        }

        $this->deleteProcedureSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export proceduresetting to a file
     */
    public function export(ExportProcedureSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'procedure_setting.'.$format;
        $filters = $request->getFilters();

        return Excel::download(new ProcedureSettingExport($this->procedureSettingService, $filters), $fileName);
    }

    private function defaultWorkFlowForFilters(array $filters): ?WorkFlow
    {
        if (isset($filters['type']) && ! isset($filters['project_id']) && ! isset($filters['parent_id'])) {
            return $this->procedureSettingService->getDefaultWorkFlowByType((string) $filters['type']);
        }

        $workFlows = $this->procedureSettingService->listByWorkFlow($filters);

        return $workFlows->firstWhere('name', 'default') ?? $workFlows->first();
    }

    private function presentWorkFlow(WorkFlow $workFlow, array $filters = []): array
    {
        $parentId = $filters['parent_id'] ?? null;

        $procedureSettings = $parentId !== null
            ? $workFlow->procedureSettings->where('parent_id', $parentId)->values()
            : $workFlow->procedureSettings->whereNull('parent_id')->values();

        return [
            'id' => $workFlow->id,
            'name' => $workFlow->name,
            'type' => $workFlow->type,
            'branches' => $workFlow->managementHierarchies
                ->where('type', 'branch')
                ->map(static fn ($branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'type' => $branch->type,
                    'company_id' => $branch->company_id,
                ])
                ->values()
                ->all(),
            'procedure-settings' => ProcedureSettingPresenter::collection($procedureSettings),
        ];
    }

    private function projectIdFromRequest(FormRequest $request): ?string
    {
        $projectId = $request->input('project_id');

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    private function authorizeProjectProcedure(string $permission): void
    {
        $this->permissionMiddleware->handle(
            request(),
            static fn ($request) => null,
            $permission,
        );
    }

    private function assertWorkFlowBelongsToProject(string $projectId, mixed $workFlowId): void
    {
        if ($workFlowId === null || $workFlowId === '') {
            return;
        }

        $query = WorkFlow::query()
            ->where('id', (string) $workFlowId)
            ->where('project_id', $projectId);

        $tenantId = tenant('id');
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('company_id', (string) $tenantId);
        }

        $query->firstOrFail(['id']);
    }

    private function assertProcedureSettingBelongsToProject(string $projectId, mixed $procedureSettingId): void
    {
        if ($procedureSettingId === null || $procedureSettingId === '') {
            return;
        }

        $this->procedureSettingService->getForProject($projectId, Uuid::fromString((string) $procedureSettingId));
    }
}

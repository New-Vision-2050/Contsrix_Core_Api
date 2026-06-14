<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingHandler;
use Modules\ProcedureSetting\Presenters\ProcedureSettingPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingListRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\ToggleBranchWorkFlowRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingCRUDService;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;
use Modules\ProcedureSetting\Exports\ProcedureSettingExport;
use Modules\ProcedureSetting\Requests\ExportProcedureSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;
use Modules\ProcedureSetting\Models\WorkFlow;

class ProcedureSettingController extends Controller
{
    public function __construct(
        private ProcedureSettingCRUDService $procedureSettingService,
        private UpdateProcedureSettingHandler $updateProcedureSettingHandler,
        private DeleteProcedureSettingHandler $deleteProcedureSettingHandler,
        private ProcedureWorkflowService $workflowService,
    ) {
    }

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
    public function approvalResponsibles(): JsonResponse
    {
        $type = (string) request()->query('type', '');

        if ($type === '') {
            return Json::error(__('The type query parameter is required.'), 422);
        }

        return Json::item(
            $this->workflowService->getApprovalResponsibles($type, (string) \Illuminate\Support\Facades\Auth::id()),
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

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow) : null);
        }

        if (isset($filters['type']) && ! isset($filters['branch_id']) && ! isset($filters['work_flow_id'])) {
            $defaultWorkFlow = $this->procedureSettingService->getDefaultWorkFlowByType((string) $filters['type']);

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow) : null);
        }

        if (isset($filters['branch_id'])) {
            $workFlow = $this->procedureSettingService->firstByWorkFlowFilters($filters);

            return Json::item($workFlow ? $this->presentWorkFlow($workFlow) : null);
        }

        $list = $this->procedureSettingService->listByWorkFlow($filters);

        return Json::items($list->map(fn (WorkFlow $workFlow): array => $this->presentWorkFlow($workFlow))->values()->all());
    }

    public function show(GetProcedureSettingRequest $request): JsonResponse
    {
        $item = $this->procedureSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProcedureSettingRequest $request): JsonResponse
    {
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
        $command = $request->createUpdateProcedureSettingCommand();
        $this->updateProcedureSettingHandler->handle($command);

        $item = $this->procedureSettingService->get($command->getId());

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProcedureSettingRequest $request): JsonResponse
    {
        $this->deleteProcedureSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export proceduresetting to a file
     *
     * @param ExportProcedureSettingRequest $request
     */
    public function export(ExportProcedureSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'procedure_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new ProcedureSettingExport($this->procedureSettingService, $filters), $fileName);
    }

    private function presentWorkFlow(WorkFlow $workFlow): array
    {
        return [
            'id'                 => $workFlow->id,
            'name'               => $workFlow->name,
            'type'               => $workFlow->type,
            'branches'           => $workFlow->managementHierarchies
                ->where('type', 'branch')
                ->map(static fn ($branch): array => [
                    'id'         => $branch->id,
                    'name'       => $branch->name,
                    'type'       => $branch->type,
                    'company_id' => $branch->company_id,
                ])
                ->values()
                ->all(),
            'procedure-settings' => ProcedureSettingPresenter::collection($workFlow->procedureSettings),
        ];
    }
}

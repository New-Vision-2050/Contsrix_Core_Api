<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingHandler;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\ProcedureSetting\Presenters\ProcedureSettingPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingListRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingCRUDService;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Ramsey\Uuid\Uuid;

class ProjectProcedureSettingController extends Controller
{
    public function __construct(
        private readonly ProcedureSettingCRUDService $procedureSettingService,
        private readonly UpdateProcedureSettingHandler $updateProcedureSettingHandler,
        private readonly DeleteProcedureSettingHandler $deleteProcedureSettingHandler,
    ) {}

    public function index(GetProcedureSettingListRequest $request): JsonResponse
    {
        $projectId = $this->resolveProjectId($request);
        $filters = $request->getFilters();
        $scopedFilters = array_merge($filters, ['project_id' => $projectId]);

        if ($filters === []) {
            $workFlows = $this->procedureSettingService->listByWorkFlow($scopedFilters);
            $defaultWorkFlow = $workFlows->firstWhere('name', 'default') ?? $workFlows->first();

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow, $scopedFilters) : null);
        }

        if (isset($filters['type']) && isset($filters['parent_id']) && ! isset($filters['branch_id']) && ! isset($filters['work_flow_id'])) {
            $workFlows = $this->procedureSettingService->listByWorkFlow($scopedFilters);
            $workFlow = $workFlows->firstWhere('name', 'default') ?? $workFlows->first();

            return Json::item($workFlow ? $this->presentWorkFlow($workFlow, $scopedFilters) : null);
        }

        if (isset($filters['type']) && ! isset($filters['branch_id']) && ! isset($filters['work_flow_id'])) {
            $workFlows = $this->procedureSettingService->listByWorkFlow($scopedFilters);
            $defaultWorkFlow = $workFlows->firstWhere('name', 'default') ?? $workFlows->first();

            return Json::item($defaultWorkFlow ? $this->presentWorkFlow($defaultWorkFlow, $scopedFilters) : null);
        }

        if (isset($filters['branch_id'])) {
            $workFlow = $this->procedureSettingService->firstByWorkFlowFilters($scopedFilters);

            return Json::item($workFlow ? $this->presentWorkFlow($workFlow, $scopedFilters) : null);
        }

        $list = $this->procedureSettingService->listByWorkFlow($scopedFilters);

        return Json::items($list->map(fn (WorkFlow $workFlow): array => $this->presentWorkFlow($workFlow, $scopedFilters))->values()->all());
    }

    public function store(CreateProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->resolveProjectId($request);

        $this->assertWorkFlowBelongsToProject($projectId, $request->input('work_flow_id'));
        $this->assertProcedureSettingBelongsToProject($projectId, $request->input('parent_id'));

        $createdItem = $this->procedureSettingService->create($request->createCreateProcedureSettingDTO());

        $presenter = new ProcedureSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function show(GetProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->resolveProjectId($request);
        $item = $this->procedureSettingService->getForProject($projectId, Uuid::fromString($request->route('id')));

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->resolveProjectId($request);
        $id = Uuid::fromString($request->route('id'));

        $this->procedureSettingService->getForProject($projectId, $id);
        $this->assertWorkFlowBelongsToProject($projectId, $request->input('work_flow_id'));
        $this->assertProcedureSettingBelongsToProject($projectId, $request->input('parent_id'));

        $command = $request->createUpdateProcedureSettingCommand();
        $this->updateProcedureSettingHandler->handle($command);

        $item = $this->procedureSettingService->getForProject($projectId, $command->getId());

        $presenter = new ProcedureSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteProcedureSettingRequest $request): JsonResponse
    {
        $projectId = $this->resolveProjectId($request);
        $id = Uuid::fromString($request->route('id'));

        $this->procedureSettingService->getForProject($projectId, $id);
        $this->deleteProcedureSettingHandler->handle($id);

        return Json::deleted();
    }

    private function resolveProjectId(FormRequest $request): string
    {
        $projectId = (string) $request->route('project_id');
        $query = ProjectManagement::query()
            ->withoutGlobalScopes()
            ->where('id', $projectId);

        $tenantId = tenant('id');
        if ($tenantId !== null && $tenantId !== '') {
            $query->where('company_id', (string) $tenantId);
        }

        $query->firstOrFail(['id']);

        return $projectId;
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

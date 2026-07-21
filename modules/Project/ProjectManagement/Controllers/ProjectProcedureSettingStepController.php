<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingStepHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingStepHandler;
use Modules\ProcedureSetting\Presenters\ProcedureSettingStepPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingStepRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingCRUDService;
use Modules\ProcedureSetting\Services\ProcedureSettingStepCRUDService;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Ramsey\Uuid\Uuid;

class ProjectProcedureSettingStepController extends Controller
{
    public function __construct(
        private readonly ProcedureSettingStepCRUDService $stepService,
        private readonly ProcedureSettingCRUDService $procedureSettingService,
        private readonly UpdateProcedureSettingStepHandler $updateHandler,
        private readonly DeleteProcedureSettingStepHandler $deleteHandler,
    ) {}

    public function index(GetProcedureSettingStepRequest $request): JsonResponse
    {
        $procedureSettingId = $this->assertProcedureSettingIsAvailableForProject($request);

        $steps = $this->stepService->getByProcedureSettingId($procedureSettingId);

        return Json::items(ProcedureSettingStepPresenter::collection($steps));
    }

    public function show(GetProcedureSettingStepRequest $request): JsonResponse
    {
        $procedureSettingId = $this->assertProcedureSettingIsAvailableForProject($request);
        $step = $this->stepService->get((int) $request->route('stepId'));

        abort_unless($step->procedure_setting_id === $procedureSettingId, 404);

        return Json::item((new ProcedureSettingStepPresenter($step))->getData());
    }

    public function store(CreateProcedureSettingStepRequest $request): JsonResponse
    {
        $this->assertProcedureSettingIsAvailableForProject($request);

        $step = $this->stepService->create($request->createCreateProcedureSettingStepDTO());

        return Json::item((new ProcedureSettingStepPresenter($step))->getData());
    }

    public function update(UpdateProcedureSettingStepRequest $request): JsonResponse
    {
        $this->assertProcedureSettingIsAvailableForProject($request);

        $command = $request->createUpdateProcedureSettingStepCommand();
        $this->updateHandler->handle($command);

        $step = $this->stepService->get($command->getId());

        return Json::item((new ProcedureSettingStepPresenter($step))->getData());
    }

    public function delete(DeleteProcedureSettingStepRequest $request): JsonResponse
    {
        $procedureSettingId = $this->assertProcedureSettingIsAvailableForProject($request);
        $step = $this->stepService->get((int) $request->route('stepId'));

        abort_unless($step->procedure_setting_id === $procedureSettingId, 404);

        $this->deleteHandler->handle($step->id);

        return Json::deleted();
    }

    private function assertProcedureSettingIsAvailableForProject(FormRequest $request): string
    {
        $projectId = $this->resolveProjectId($request);
        $procedureSettingId = (string) $request->route('procedureSettingId');

        abort_unless(Uuid::isValid($procedureSettingId), 404);

        $this->procedureSettingService->getForProject(
            $projectId,
            Uuid::fromString($procedureSettingId),
        );

        return $procedureSettingId;
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
}

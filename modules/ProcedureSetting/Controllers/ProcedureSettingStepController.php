<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingStepHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingStepHandler;
use Modules\ProcedureSetting\Presenters\ProcedureSettingStepPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingStepRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingStepRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingStepCRUDService;

class ProcedureSettingStepController extends Controller
{
    public function __construct(
        private ProcedureSettingStepCRUDService $stepService,
        private UpdateProcedureSettingStepHandler $updateHandler,
        private DeleteProcedureSettingStepHandler $deleteHandler,
    ) {
    }

    public function index(GetProcedureSettingStepRequest $request): JsonResponse
    {
        $procedureSettingId = $request->route('procedureSettingId');

        $steps = $this->stepService->getByProcedureSettingId($procedureSettingId);

        return Json::items(ProcedureSettingStepPresenter::collection($steps));
    }

    public function show(GetProcedureSettingStepRequest $request): JsonResponse
    {
        $step = $this->stepService->get((int) $request->route('stepId'));

        $presenter = new ProcedureSettingStepPresenter($step);

        return Json::item($presenter->getData());
    }

    public function store(CreateProcedureSettingStepRequest $request): JsonResponse
    {
        $step = $this->stepService->create($request->createCreateProcedureSettingStepDTO());

        $presenter = new ProcedureSettingStepPresenter($step);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProcedureSettingStepRequest $request): JsonResponse
    {
        $command = $request->createUpdateProcedureSettingStepCommand();
        $this->updateHandler->handle($command);

        $step = $this->stepService->get($command->getId());

        $presenter = new ProcedureSettingStepPresenter($step);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteProcedureSettingStepRequest $request): JsonResponse
    {
        $this->deleteHandler->handle((int) $request->route('stepId'));

        return Json::deleted();
    }
}

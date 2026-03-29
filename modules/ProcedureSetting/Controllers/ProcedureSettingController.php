<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ProcedureSetting\Handlers\DeleteProcedureSettingHandler;
use Modules\ProcedureSetting\Handlers\UpdateProcedureSettingHandler;
use Modules\ProcedureSetting\Presenters\ProcedureSettingPresenter;
use Modules\ProcedureSetting\Requests\CreateProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\DeleteProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingListRequest;
use Modules\ProcedureSetting\Requests\GetProcedureSettingRequest;
use Modules\ProcedureSetting\Requests\UpdateProcedureSettingRequest;
use Modules\ProcedureSetting\Services\ProcedureSettingCRUDService;
use Modules\ProcedureSetting\Exports\ProcedureSettingExport;
use Modules\ProcedureSetting\Requests\ExportProcedureSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class ProcedureSettingController extends Controller
{
    public function __construct(
        private ProcedureSettingCRUDService $procedureSettingService,
        private UpdateProcedureSettingHandler $updateProcedureSettingHandler,
        private DeleteProcedureSettingHandler $deleteProcedureSettingHandler,
    ) {
    }

    public function index(GetProcedureSettingListRequest $request): JsonResponse
    {
        $list = $this->procedureSettingService->list();

        return Json::items(ProcedureSettingPresenter::collection($list));
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
}

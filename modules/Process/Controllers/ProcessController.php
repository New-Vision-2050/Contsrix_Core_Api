<?php

declare(strict_types=1);

namespace Modules\Process\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Process\Handlers\DeleteProcessHandler;
use Modules\Process\Handlers\UpdateProcessHandler;
use Modules\Process\Presenters\ProcessPresenter;
use Modules\Process\Requests\CreateProcessRequest;
use Modules\Process\Requests\DeleteProcessRequest;
use Modules\Process\Requests\GetProcessListRequest;
use Modules\Process\Requests\GetProcessRequest;
use Modules\Process\Requests\UpdateProcessRequest;
use Modules\Process\Services\ProcessCRUDService;
use Modules\Process\Exports\ProcessExport;
use Modules\Process\Requests\ExportProcessRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class ProcessController extends Controller
{
    public function __construct(
        private ProcessCRUDService $processService,
        private UpdateProcessHandler $updateProcessHandler,
        private DeleteProcessHandler $deleteProcessHandler,
    ) {
    }

    public function index(GetProcessListRequest $request): JsonResponse
    {
        $list = $this->processService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ProcessPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetProcessRequest $request): JsonResponse
    {
        $item = $this->processService->get(Uuid::fromString($request->route('id')));

        $presenter = new ProcessPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateProcessRequest $request): JsonResponse
    {
        $createdItem = $this->processService->create($request->createCreateProcessDTO());

        $presenter = new ProcessPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateProcessRequest $request): JsonResponse
    {
        $command = $request->createUpdateProcessCommand();
        $this->updateProcessHandler->handle($command);

        $item = $this->processService->get($command->getId());

        $presenter = new ProcessPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteProcessRequest $request): JsonResponse
    {
        $this->deleteProcessHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export process to a file
     *
     * @param ExportProcessRequest $request
     */
    public function export(ExportProcessRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'process.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new ProcessExport($this->processService, $filters), $fileName);
    }
}

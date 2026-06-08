<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Shared/Process\Handlers\DeleteShared/ProcessHandler;
use Modules\Shared/Process\Handlers\UpdateShared/ProcessHandler;
use Modules\Shared/Process\Presenters\Shared/ProcessPresenter;
use Modules\Shared/Process\Requests\CreateShared/ProcessRequest;
use Modules\Shared/Process\Requests\DeleteShared/ProcessRequest;
use Modules\Shared/Process\Requests\GetShared/ProcessListRequest;
use Modules\Shared/Process\Requests\GetShared/ProcessRequest;
use Modules\Shared/Process\Requests\UpdateShared/ProcessRequest;
use Modules\Shared/Process\Services\Shared/ProcessCRUDService;
use Modules\Shared/Process\Exports\Shared/ProcessExport;
use Modules\Shared/Process\Requests\ExportShared/ProcessRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class Shared/ProcessController extends Controller
{
    public function __construct(
        private Shared/ProcessCRUDService $shared/ProcessService,
        private UpdateShared/ProcessHandler $updateShared/ProcessHandler,
        private DeleteShared/ProcessHandler $deleteShared/ProcessHandler,
    ) {
    }

    public function index(GetShared/ProcessListRequest $request): JsonResponse
    {
        $list = $this->shared/ProcessService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(Shared/ProcessPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetShared/ProcessRequest $request): JsonResponse
    {
        $item = $this->shared/ProcessService->get(Uuid::fromString($request->route('id')));

        $presenter = new Shared/ProcessPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateShared/ProcessRequest $request): JsonResponse
    {
        $createdItem = $this->shared/ProcessService->create($request->createCreateShared/ProcessDTO());

        $presenter = new Shared/ProcessPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateShared/ProcessRequest $request): JsonResponse
    {
        $command = $request->createUpdateShared/ProcessCommand();
        $this->updateShared/ProcessHandler->handle($command);

        $item = $this->shared/ProcessService->get($command->getId());

        $presenter = new Shared/ProcessPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteShared/ProcessRequest $request): JsonResponse
    {
        $this->deleteShared/ProcessHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export shared/process to a file
     *
     * @param ExportShared/ProcessRequest $request
     */
    public function export(ExportShared/ProcessRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'shared/_process.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new Shared/ProcessExport($this->shared/ProcessService, $filters), $fileName);
    }
}

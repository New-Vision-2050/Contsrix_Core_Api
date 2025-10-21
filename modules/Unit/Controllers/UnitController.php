<?php

declare(strict_types=1);

namespace Modules\Unit\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Unit\Handlers\DeleteUnitHandler;
use Modules\Unit\Handlers\UpdateUnitHandler;
use Modules\Unit\Presenters\UnitPresenter;
use Modules\Unit\Requests\CreateUnitRequest;
use Modules\Unit\Requests\DeleteUnitRequest;
use Modules\Unit\Requests\GetUnitListRequest;
use Modules\Unit\Requests\GetUnitRequest;
use Modules\Unit\Requests\UpdateUnitRequest;
use Modules\Unit\Services\UnitCRUDService;
use Modules\Unit\Exports\UnitExport;
use Modules\Unit\Requests\ExportUnitRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class UnitController extends Controller
{
    public function __construct(
        private UnitCRUDService $unitService,
        private UpdateUnitHandler $updateUnitHandler,
        private DeleteUnitHandler $deleteUnitHandler,
    ) {
    }

    public function index(GetUnitListRequest $request): JsonResponse
    {
        $list = $this->unitService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(UnitPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetUnitRequest $request): JsonResponse
    {
        $item = $this->unitService->get(Uuid::fromString($request->route('id')));

        $presenter = new UnitPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateUnitRequest $request): JsonResponse
    {
        $createdItem = $this->unitService->create($request->createCreateUnitDTO());

        $presenter = new UnitPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateUnitRequest $request): JsonResponse
    {
        $command = $request->createUpdateUnitCommand();
        $this->updateUnitHandler->handle($command);

        $item = $this->unitService->get($command->getId());

        $presenter = new UnitPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteUnitRequest $request): JsonResponse
    {
        $this->deleteUnitHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export unit to a file
     *
     * @param ExportUnitRequest $request
     */
    public function export(ExportUnitRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'unit.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new UnitExport($this->unitService, $filters), $fileName);
    }
}

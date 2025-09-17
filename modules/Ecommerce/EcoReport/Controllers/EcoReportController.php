<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoReport\Handlers\DeleteEcoReportHandler;
use Modules\Ecommerce\EcoReport\Handlers\UpdateEcoReportHandler;
use Modules\Ecommerce\EcoReport\Presenters\EcoReportPresenter;
use Modules\Ecommerce\EcoReport\Requests\CreateEcoReportRequest;
use Modules\Ecommerce\EcoReport\Requests\DeleteEcoReportRequest;
use Modules\Ecommerce\EcoReport\Requests\GetEcoReportListRequest;
use Modules\Ecommerce\EcoReport\Requests\GetEcoReportRequest;
use Modules\Ecommerce\EcoReport\Requests\UpdateEcoReportRequest;
use Modules\Ecommerce\EcoReport\Services\EcoReportCRUDService;
use Modules\Ecommerce\EcoReport\Exports\EcoReportExport;
use Modules\Ecommerce\EcoReport\Requests\ExportEcoReportRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoReportController extends Controller
{
    public function __construct(
        private EcoReportCRUDService $ecoReportService,
        private UpdateEcoReportHandler $updateEcoReportHandler,
        private DeleteEcoReportHandler $deleteEcoReportHandler,
    ) {
    }

    public function index(GetEcoReportListRequest $request): JsonResponse
    {
        $list = $this->ecoReportService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoReportPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoReportRequest $request): JsonResponse
    {
        $item = $this->ecoReportService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoReportPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoReportRequest $request): JsonResponse
    {
        $createdItem = $this->ecoReportService->create($request->createCreateEcoReportDTO());

        $presenter = new EcoReportPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoReportRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoReportCommand();
        $this->updateEcoReportHandler->handle($command);

        $item = $this->ecoReportService->get($command->getId());

        $presenter = new EcoReportPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoReportRequest $request): JsonResponse
    {
        $this->deleteEcoReportHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoreport to a file
     *
     * @param ExportEcoReportRequest $request
     */
    public function export(ExportEcoReportRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_report.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoReportExport($this->ecoReportService, $filters), $fileName);
    }
}

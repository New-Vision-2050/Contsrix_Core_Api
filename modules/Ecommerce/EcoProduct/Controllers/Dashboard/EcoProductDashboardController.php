<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoProduct\Presenters\Dashboard\EcoProductDashboardPresenter;
use Modules\Ecommerce\EcoProduct\Presenters\Dashboard\EcoProductDashboardDetailsPresenter;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\CreateEcoProductDashboardRequest;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\DeleteEcoProductDashboardRequest;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\GetEcoProductListDashboardRequest;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\GetEcoProductDashboardRequest;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\UpdateEcoProductDashboardRequest;
use Modules\Ecommerce\EcoProduct\Services\Dashboard\EcoProductDashboardCRUDService;
use Modules\Ecommerce\EcoProduct\Requests\Dashboard\ExportEcoProductDashboardRequest;
use Modules\Ecommerce\EcoProduct\Handlers\Dashboard\UpdateEcoProductDashboardHandler;
use Modules\Ecommerce\EcoProduct\Handlers\Dashboard\DeleteEcoProductDashboardHandler;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoProduct\Exports\EcoProductExport;
use Ramsey\Uuid\Uuid;

class EcoProductDashboardController extends Controller
{
    public function __construct(
        private EcoProductDashboardCRUDService $ecoProductService,
        private UpdateEcoProductDashboardHandler $updateEcoProductHandler,
        private DeleteEcoProductDashboardHandler $deleteEcoProductHandler,
    ) {
    }

    public function index(GetEcoProductListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoProductService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoProductDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoProductDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoProductService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoProductDashboardDetailsPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoProductDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoProductService->create($request->createCreateEcoProductDTO());

        $presenter = new EcoProductDashboardDetailsPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoProductDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoProductCommand();
        $this->updateEcoProductHandler->handle($command);

        $item = $this->ecoProductService->get($command->getId());

        $presenter = new EcoProductDashboardDetailsPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteEcoProductDashboardRequest $request): JsonResponse
    {
        $this->deleteEcoProductHandler->handle(Uuid::fromString($request->route('id')));

        return Json::success();
    }

    public function export(ExportEcoProductDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_products.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoProductExport($this->ecoProductService, $filters), $fileName);
    }

    public function getStatistics(): JsonResponse
    {
        $statistics = $this->ecoProductService->getProductStatistics();

        return Json::item($statistics);
    }
}

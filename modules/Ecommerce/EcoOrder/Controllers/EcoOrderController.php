<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrder\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoOrder\Handlers\DeleteEcoOrderHandler;
use Modules\Ecommerce\EcoOrder\Handlers\UpdateEcoOrderHandler;
use Modules\Ecommerce\EcoOrder\Presenters\EcoOrderPresenter;
use Modules\Ecommerce\EcoOrder\Requests\CreateEcoOrderRequest;
use Modules\Ecommerce\EcoOrder\Requests\DeleteEcoOrderRequest;
use Modules\Ecommerce\EcoOrder\Requests\GetEcoOrderListRequest;
use Modules\Ecommerce\EcoOrder\Requests\GetEcoOrderRequest;
use Modules\Ecommerce\EcoOrder\Requests\UpdateEcoOrderRequest;
use Modules\Ecommerce\EcoOrder\Services\EcoOrderCRUDService;
use Modules\Ecommerce\EcoOrder\Exports\EcoOrderExport;
use Modules\Ecommerce\EcoOrder\Requests\ExportEcoOrderRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoOrderController extends Controller
{
    public function __construct(
        private EcoOrderCRUDService $ecoOrderService,
        private UpdateEcoOrderHandler $updateEcoOrderHandler,
        private DeleteEcoOrderHandler $deleteEcoOrderHandler,
    ) {
    }

    public function index(GetEcoOrderListRequest $request): JsonResponse
    {
        $list = $this->ecoOrderService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoOrderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoOrderRequest $request): JsonResponse
    {
        $item = $this->ecoOrderService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoOrderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoOrderRequest $request): JsonResponse
    {
        $createdItem = $this->ecoOrderService->create($request->createCreateEcoOrderDTO());

        $presenter = new EcoOrderPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoOrderRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoOrderCommand();
        $this->updateEcoOrderHandler->handle($command);

        $item = $this->ecoOrderService->get($command->getId());

        $presenter = new EcoOrderPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoOrderRequest $request): JsonResponse
    {
        $this->deleteEcoOrderHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoorder to a file
     *
     * @param ExportEcoOrderRequest $request
     */
    public function export(ExportEcoOrderRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_order.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoOrderExport($this->ecoOrderService, $filters), $fileName);
    }

    /**
     * Get order statistics for dashboard cards
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->ecoOrderService->getOrderStatistics();
        
        return Json::item($statistics);
    }
}

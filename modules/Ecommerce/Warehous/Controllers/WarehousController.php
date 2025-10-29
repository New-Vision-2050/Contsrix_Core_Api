<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Warehous\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Warehous\Handlers\DeleteWarehousHandler;
use Modules\Ecommerce\Warehous\Handlers\UpdateWarehousHandler;
use Modules\Ecommerce\Warehous\Presenters\WarehousPresenter;
use Modules\Ecommerce\Warehous\Requests\CreateWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\DeleteWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\GetWarehousListRequest;
use Modules\Ecommerce\Warehous\Requests\GetWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\UpdateWarehousRequest;
use Modules\Ecommerce\Warehous\Requests\Dashboard\ExportWarehousRequest;
use Modules\Ecommerce\Warehous\Services\WarehousCRUDService;
use Ramsey\Uuid\Uuid;

class WarehousController extends Controller
{
    public function __construct(
        private WarehousCRUDService $warehousService,
        private UpdateWarehousHandler $updateWarehousHandler,
        private DeleteWarehousHandler $deleteWarehousHandler,
    ) {
    }

    public function index(GetWarehousListRequest $request): JsonResponse
    {
        $list = $this->warehousService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WarehousPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWarehousRequest $request): JsonResponse
    {
        $item = $this->warehousService->get(Uuid::fromString($request->route('id')));

        $presenter = new WarehousPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWarehousRequest $request): JsonResponse
    {
        $createdItem = $this->warehousService->create($request->createCreateWarehousDTO());

        $presenter = new WarehousPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWarehousRequest $request): JsonResponse
    {
        $command = $request->createUpdateWarehousCommand();
        $this->updateWarehousHandler->handle($command);

        $item = $this->warehousService->get($command->getId());

        $presenter = new WarehousPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWarehousRequest $request): JsonResponse
    {
        $this->deleteWarehousHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Get warehouse statistics for dashboard cards
     */
    public function getStatistics(): JsonResponse
    {
        $statistics = $this->warehousService->getWarehouseStatistics();
        
        return Json::item($statistics);
    }

    /**
     * Export warehouses to Excel or CSV
     */
    public function export(ExportWarehousRequest $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $warehouseIds = $request->input('ids');
        $format = $request->input('format', 'xlsx');
        
        $filters = [
            'company_id' => $request->input('company_id'),
            'country_id' => $request->input('country_id'),
            'city_id' => $request->input('city_id'),
            'is_active' => $request->input('is_active'),
            'is_default' => $request->input('is_default'),
            'has_products' => $request->input('has_products'),
            'min_products_count' => $request->input('min_products_count'),
            'max_products_count' => $request->input('max_products_count'),
            'district' => $request->input('district'),
            'street' => $request->input('street'),
            'latitude_from' => $request->input('latitude_from'),
            'latitude_to' => $request->input('latitude_to'),
            'longitude_from' => $request->input('longitude_from'),
            'longitude_to' => $request->input('longitude_to'),
            'near_location' => $request->input('near_location'),
            'created_from' => $request->input('created_from'),
            'created_to' => $request->input('created_to'),
        ];

        if ($format === 'csv') {
            return $this->warehousService->exportToCsv($warehouseIds, $filters);
        }

        return $this->warehousService->exportToExcel($warehouseIds, $filters);
    }
}

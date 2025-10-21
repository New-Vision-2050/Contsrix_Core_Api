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
        
        $createdItem = $this->ecoProductService->create($request->createNewEcoProductDTO());
       
        $presenter = new EcoProductDashboardDetailsPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(CreateEcoProductDashboardRequest $request, string $id): JsonResponse
    {
        try {
            // Find the product
            $product = $this->ecoProductService->get(Uuid::fromString($id));
            
            if (!$product) {
                return Json::error('المنتج غير موجود', 404);
            }

            // Create DTO from request
            $dto = $request->createNewEcoProductDTO();
            
            // Update the product
            $updatedProduct = $this->ecoProductService->update($product, $dto);

            $presenter = new EcoProductDashboardDetailsPresenter($updatedProduct);

            return Json::item($presenter->getData());
        } catch (\Exception $e) {
            return Json::error('فشل في تحديث المنتج: ' . $e->getMessage(), 500);
        }
    }

    public function delete(DeleteEcoProductDashboardRequest $request): JsonResponse
    {
        $this->deleteEcoProductHandler->handle(Uuid::fromString($request->route('id')));

        return Json::success();
    }

    /**
     * Toggle product visibility (active/inactive)
     */
    public function toggleVisibility(string $id): JsonResponse
    {
        try {
            $result = $this->ecoProductService->toggleVisibility(Uuid::fromString($id));
            
            return Json::item([
                'message' => $result['message'],
                'is_visible' => $result['is_visible'],
                'status_text' => $result['status_text']
            ]);
        } catch (\Exception $e) {
            return Json::error('فشل في تغيير حالة المنتج: ' . $e->getMessage(), 500);
        }
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

    /**
     * Get enhanced product statistics with new fields
     */
    public function getEnhancedStatistics(): JsonResponse
    {
        $statistics = $this->ecoProductService->getEnhancedProductStatistics();

        return Json::item($statistics);
    }
}

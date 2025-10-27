<?php

declare(strict_types=1);

namespace Modules\Ecommerce\DealDay\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\DealDay\Handlers\DeleteDealDayHandler;
use Modules\Ecommerce\DealDay\Handlers\UpdateDealDayHandler;
use Modules\Ecommerce\DealDay\Presenters\DealDayPresenter;
use Modules\Ecommerce\DealDay\Requests\CreateDealDayRequest;
use Modules\Ecommerce\DealDay\Requests\DeleteDealDayRequest;
use Modules\Ecommerce\DealDay\Requests\GetDealDayListRequest;
use Modules\Ecommerce\DealDay\Requests\GetDealDayRequest;
use Modules\Ecommerce\DealDay\Requests\SearchDealDayRequest;
use Modules\Ecommerce\DealDay\Requests\UpdateDealDayRequest;
use Modules\Ecommerce\DealDay\Services\DealDayCRUDService;
use Modules\Ecommerce\DealDay\Exports\DealDayExport;
use Modules\Ecommerce\DealDay\Requests\ExportDealDayRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class DealDayController extends Controller
{
    public function __construct(
        private DealDayCRUDService $dealDayService,
        private UpdateDealDayHandler $updateDealDayHandler,
        private DeleteDealDayHandler $deleteDealDayHandler,
    ) {
    }

    public function index(GetDealDayListRequest $request): JsonResponse
    {
        // Extract filters from request
        $filters = $this->extractFilters($request);
        
        $list = $this->dealDayService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10),
            ['company', 'product'], // Load relationships
            $filters // Apply filters
        );

        return Json::items(DealDayPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetDealDayRequest $request): JsonResponse
    {
        $item = $this->dealDayService->getWithRelations(Uuid::fromString($request->route('id')));

        $presenter = new DealDayPresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }

    public function store(CreateDealDayRequest $request): JsonResponse
    {
        $createdItem = $this->dealDayService->create($request->createCreateDealDayDTO());
        
        // Load relationships for presenter
        $itemWithRelations = $this->dealDayService->getWithRelations(Uuid::fromString($createdItem->id));

        $presenter = new DealDayPresenter($itemWithRelations);

        return Json::item($presenter->getData());
    }

    public function update(UpdateDealDayRequest $request): JsonResponse
    {
        $command = $request->createUpdateDealDayCommand();
        $this->updateDealDayHandler->handle($command);

        $item = $this->dealDayService->getWithRelations($command->getId());

        $presenter = new DealDayPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteDealDayRequest $request): JsonResponse
    {
        $this->deleteDealDayHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Toggle deal day active status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $result = $this->dealDayService->toggleStatus(Uuid::fromString($id));
            
            return Json::item([
                'message' => $result['message'],
                'is_active' => $result['is_active'],
                'status_text' => $result['status_text']
            ]);
        } catch (\Exception $e) {
            return Json::error('فشل في تغيير حالة العرض: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Search deal days with advanced filters
     */
    public function search(SearchDealDayRequest $request): JsonResponse
    {
        $filters = $request->getFilters();
        
        $list = $this->dealDayService->search(
            $filters,
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 10)
        );

        return Json::items(DealDayPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    /**
     * Get deal day statistics cards for dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->dealDayService->getDealDayStatistics();

        return Json::item($stats);
    }

    /**
     * Extract filters from request
     */
    private function extractFilters(GetDealDayListRequest $request): array
    {
        return array_filter([
            'search' => $request->get('search'),
            'name' => $request->get('name'),
            'company_id' => $request->get('company_id'),
            'product_id' => $request->get('product_id'),
            'discount_type' => $request->get('discount_type'),
            'min_discount_value' => $request->get('min_discount_value'),
            'max_discount_value' => $request->get('max_discount_value'),
            'is_active' => $request->get('is_active'),
            'active_only' => $request->get('active_only'),
            'inactive_only' => $request->get('inactive_only'),
            'created_from' => $request->get('created_from'),
            'created_to' => $request->get('created_to'),
            'updated_from' => $request->get('updated_from'),
            'updated_to' => $request->get('updated_to'),
            'order_by' => $request->get('order_by'),
            'order_direction' => $request->get('order_direction'),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Export dealday to a file
     *
     * @param ExportDealDayRequest $request
     */
    public function export(ExportDealDayRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'deal_day.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new DealDayExport($this->dealDayService, $filters), $fileName);
    }
}

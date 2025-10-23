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
        $list = $this->dealDayService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(DealDayPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetDealDayRequest $request): JsonResponse
    {
        $item = $this->dealDayService->get(Uuid::fromString($request->route('id')));

        $presenter = new DealDayPresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }

    public function store(CreateDealDayRequest $request): JsonResponse
    {
        $createdItem = $this->dealDayService->create($request->createCreateDealDayDTO());

        $presenter = new DealDayPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateDealDayRequest $request): JsonResponse
    {
        $command = $request->createUpdateDealDayCommand();
        $this->updateDealDayHandler->handle($command);

        $item = $this->dealDayService->get($command->getId());

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
     * Get deal day statistics cards for dashboard
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->dealDayService->getDealDayStatistics();

        return Json::item($stats);
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

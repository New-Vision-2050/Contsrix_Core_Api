<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FlashDeal\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\FlashDeal\Handlers\DeleteFlashDealHandler;
use Modules\Ecommerce\FlashDeal\Handlers\UpdateFlashDealHandler;
use Modules\Ecommerce\FlashDeal\Presenters\FlashDealPresenter;
use Modules\Ecommerce\FlashDeal\Requests\CreateFlashDealRequest;
use Modules\Ecommerce\FlashDeal\Requests\DeleteFlashDealRequest;
use Modules\Ecommerce\FlashDeal\Requests\GetFlashDealListRequest;
use Modules\Ecommerce\FlashDeal\Requests\GetFlashDealRequest;
use Modules\Ecommerce\FlashDeal\Requests\UpdateFlashDealRequest;
use Modules\Ecommerce\FlashDeal\Services\FlashDealCRUDService;
use Modules\Ecommerce\FlashDeal\Exports\FlashDealExport;
use Modules\Ecommerce\FlashDeal\Requests\ExportFlashDealRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class FlashDealController extends Controller
{
    public function __construct(
        private FlashDealCRUDService $flashDealService,
        private UpdateFlashDealHandler $updateFlashDealHandler,
        private DeleteFlashDealHandler $deleteFlashDealHandler,
    ) {
    }

    public function index(GetFlashDealListRequest $request): JsonResponse
    {
        $list = $this->flashDealService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FlashDealPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFlashDealRequest $request): JsonResponse
    {
        $item = $this->flashDealService->get(Uuid::fromString($request->route('id')));

        $presenter = new FlashDealPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFlashDealRequest $request): JsonResponse
    {
        $image = $request->hasFile('flash_deal_image') ? $request->file('flash_deal_image') : null;
        
        $createdItem = $this->flashDealService->create($request->createFlashDealDTO(), $image);

        $presenter = new FlashDealPresenter($createdItem);

        return Json::item($presenter->getData(), message: 'تم إنشاء العرض بنجاح');
    }

    public function update(UpdateFlashDealRequest $request): JsonResponse
    {
        $command = $request->createUpdateFlashDealCommand();
        $image = $request->hasFile('flash_deal_image') ? $request->file('flash_deal_image') : null;
        
        // Use service update method with media handling
        $data = array_filter($command->toArray(), fn($value) => $value !== null);
        $item = $this->flashDealService->update($command->id, $data, $image);

        $presenter = new FlashDealPresenter($item);

        return Json::item($presenter->getData(), message: 'تم تحديث العرض بنجاح');
    }

    public function toggleStatus(GetFlashDealRequest $request): JsonResponse
    {
        $flashDealId = Uuid::fromString($request->route('id'));
        $updatedFlashDeal = $this->flashDealService->toggleStatus($flashDealId);
        
        $presenter = new FlashDealPresenter($updatedFlashDeal);
        
        $message = $updatedFlashDeal->is_active ? 'تم تفعيل العرض بنجاح' : 'تم إلغاء تفعيل العرض بنجاح';
        
        return Json::item($presenter->getData(), message: $message);
    }

    public function delete(DeleteFlashDealRequest $request): JsonResponse
    {
        $this->deleteFlashDealHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export flashdeal to a file
     *
     * @param ExportFlashDealRequest $request
     */
    public function export(ExportFlashDealRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'flash_deal.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new FlashDealExport($this->flashDealService, $filters), $fileName);
    }
}

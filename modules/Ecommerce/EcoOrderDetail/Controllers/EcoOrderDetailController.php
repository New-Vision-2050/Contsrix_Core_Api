<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoOrderDetail\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoOrderDetail\Handlers\DeleteEcoOrderDetailHandler;
use Modules\Ecommerce\EcoOrderDetail\Handlers\UpdateEcoOrderDetailHandler;
use Modules\Ecommerce\EcoOrderDetail\Presenters\EcoOrderDetailPresenter;
use Modules\Ecommerce\EcoOrderDetail\Requests\CreateEcoOrderDetailRequest;
use Modules\Ecommerce\EcoOrderDetail\Requests\DeleteEcoOrderDetailRequest;
use Modules\Ecommerce\EcoOrderDetail\Requests\GetEcoOrderDetailListRequest;
use Modules\Ecommerce\EcoOrderDetail\Requests\GetEcoOrderDetailRequest;
use Modules\Ecommerce\EcoOrderDetail\Requests\UpdateEcoOrderDetailRequest;
use Modules\Ecommerce\EcoOrderDetail\Services\EcoOrderDetailCRUDService;
use Modules\Ecommerce\EcoOrderDetail\Exports\EcoOrderDetailExport;
use Modules\Ecommerce\EcoOrderDetail\Requests\ExportEcoOrderDetailRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoOrderDetailController extends Controller
{
    public function __construct(
        private EcoOrderDetailCRUDService $ecoOrderDetailService,
        private UpdateEcoOrderDetailHandler $updateEcoOrderDetailHandler,
        private DeleteEcoOrderDetailHandler $deleteEcoOrderDetailHandler,
    ) {
    }

    public function index(GetEcoOrderDetailListRequest $request): JsonResponse
    {
        $list = $this->ecoOrderDetailService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoOrderDetailPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoOrderDetailRequest $request): JsonResponse
    {
        $item = $this->ecoOrderDetailService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoOrderDetailPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoOrderDetailRequest $request): JsonResponse
    {
        $createdItem = $this->ecoOrderDetailService->create($request->createCreateEcoOrderDetailDTO());

        $presenter = new EcoOrderDetailPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoOrderDetailRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoOrderDetailCommand();
        $this->updateEcoOrderDetailHandler->handle($command);

        $item = $this->ecoOrderDetailService->get($command->getId());

        $presenter = new EcoOrderDetailPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoOrderDetailRequest $request): JsonResponse
    {
        $this->deleteEcoOrderDetailHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoorderdetail to a file
     *
     * @param ExportEcoOrderDetailRequest $request
     */
    public function export(ExportEcoOrderDetailRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_order_detail.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoOrderDetailExport($this->ecoOrderDetailService, $filters), $fileName);
    }
}

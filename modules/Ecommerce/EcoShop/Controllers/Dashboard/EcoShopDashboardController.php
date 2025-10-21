<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoShop\Presenters\Dashboard\EcoShopDashboardPresenter;
use Modules\Ecommerce\EcoShop\Requests\Dashboard\CreateEcoShopDashboardRequest;
use Modules\Ecommerce\EcoShop\Requests\Dashboard\UpdateEcoShopDashboardRequest;
use Modules\Ecommerce\EcoShop\Requests\Dashboard\GetEcoShopDashboardRequest;
use Modules\Ecommerce\EcoShop\Requests\Dashboard\ExportEcoShopDashboardRequest;
use Modules\Ecommerce\EcoShop\Services\Dashboard\EcoShopDashboardCRUDService;
use Modules\Ecommerce\EcoShop\Handlers\Dashboard\UpdateEcoShopDashboardHandler;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoShop\Exports\EcoShopExport;
use Ramsey\Uuid\Uuid;

class EcoShopDashboardController extends Controller
{
    public function __construct(
        private EcoShopDashboardCRUDService $ecoShopService,
        private UpdateEcoShopDashboardHandler $updateEcoShopHandler,
    ) {
    }

    public function show(GetEcoShopDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoShopService->get(Uuid::fromString(tenant("id")));

        $presenter = new EcoShopDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoShopDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoShopService->upsert($request->createCreateEcoShopDTO());

        $presenter = new EcoShopDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function export(ExportEcoShopDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_shop.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoShopExport($this->ecoShopService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShop\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoShop\Handlers\DeleteEcoShopHandler;
use Modules\Ecommerce\EcoShop\Handlers\UpdateEcoShopHandler;
use Modules\Ecommerce\EcoShop\Presenters\EcoShopPresenter;
use Modules\Ecommerce\EcoShop\Requests\CreateEcoShopRequest;
use Modules\Ecommerce\EcoShop\Requests\DeleteEcoShopRequest;
use Modules\Ecommerce\EcoShop\Requests\GetEcoShopListRequest;
use Modules\Ecommerce\EcoShop\Requests\GetEcoShopRequest;
use Modules\Ecommerce\EcoShop\Requests\UpdateEcoShopRequest;
use Modules\Ecommerce\EcoShop\Requests\UpsertEcoShopRequest;
use Modules\Ecommerce\EcoShop\Services\EcoShopCRUDService;
use Modules\Ecommerce\EcoShop\Exports\EcoShopExport;
use Modules\Ecommerce\EcoShop\Requests\ExportEcoShopRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoShopController extends Controller
{
    public function __construct(
        private EcoShopCRUDService $ecoShopService,
        private UpdateEcoShopHandler $updateEcoShopHandler,
        private DeleteEcoShopHandler $deleteEcoShopHandler,
    ) {
    }

    public function show(GetEcoShopRequest $request): JsonResponse
    {
        $item = $this->ecoShopService->get(Uuid::fromString(tenant("id")));

        $presenter = new EcoShopPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoShopRequest $request): JsonResponse
    {
        $createdItem = $this->ecoShopService->upsert($request->createCreateEcoShopDTO());

        $presenter = new EcoShopPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function export(ExportEcoShopRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_shop.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoShopExport($this->ecoShopService, $filters), $fileName);
    }
}

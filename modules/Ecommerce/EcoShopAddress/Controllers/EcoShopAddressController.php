<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoShopAddress\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoShopAddress\Handlers\DeleteEcoShopAddressHandler;
use Modules\Ecommerce\EcoShopAddress\Handlers\UpdateEcoShopAddressHandler;
use Modules\Ecommerce\EcoShopAddress\Presenters\EcoShopAddressPresenter;
use Modules\Ecommerce\EcoShopAddress\Requests\CreateEcoShopAddressRequest;
use Modules\Ecommerce\EcoShopAddress\Requests\DeleteEcoShopAddressRequest;
use Modules\Ecommerce\EcoShopAddress\Requests\GetEcoShopAddressListRequest;
use Modules\Ecommerce\EcoShopAddress\Requests\GetEcoShopAddressRequest;
use Modules\Ecommerce\EcoShopAddress\Requests\UpdateEcoShopAddressRequest;
use Modules\Ecommerce\EcoShopAddress\Services\EcoShopAddressCRUDService;
use Modules\Ecommerce\EcoShopAddress\Exports\EcoShopAddressExport;
use Modules\Ecommerce\EcoShopAddress\Requests\ExportEcoShopAddressRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoShopAddressController extends Controller
{
    public function __construct(
        private EcoShopAddressCRUDService $ecoShopAddressService,
        private UpdateEcoShopAddressHandler $updateEcoShopAddressHandler,
        private DeleteEcoShopAddressHandler $deleteEcoShopAddressHandler,
    ) {
    }

    public function show(GetEcoShopAddressRequest $request): JsonResponse
    {
        $item = $this->ecoShopAddressService->get(Uuid::fromString(tenant("id")));

        $presenter = new EcoShopAddressPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoShopAddressRequest $request): JsonResponse
    {
        $createdItem = $this->ecoShopAddressService->upsert($request->createCreateEcoShopAddressDTO());

        $presenter = new EcoShopAddressPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    /**
     * Export ecoshopaddress to a file
     *
     * @param ExportEcoShopAddressRequest $request
     */
    public function export(ExportEcoShopAddressRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_shop_address.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoShopAddressExport($this->ecoShopAddressService, $filters), $fileName);
    }
}

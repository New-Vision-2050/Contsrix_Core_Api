<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAddress\Presenters\Dashboard\EcoAddressDashboardPresenter;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\CreateEcoAddressDashboardRequest;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\DeleteEcoAddressDashboardRequest;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\GetEcoAddressListDashboardRequest;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\GetEcoAddressDashboardRequest;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\UpdateEcoAddressDashboardRequest;
use Modules\Ecommerce\EcoAddress\Services\Dashboard\EcoAddressDashboardCRUDService;
use Modules\Ecommerce\EcoAddress\Exports\EcoAddressExport;
use Modules\Ecommerce\EcoAddress\Requests\Dashboard\ExportEcoAddressDashboardRequest;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoAddress\Handlers\Dashboard\DeleteEcoAddressDashboardHandler;
use Modules\Ecommerce\EcoAddress\Handlers\Dashboard\UpdateEcoAddressDashboardHandler;
use Ramsey\Uuid\Uuid;

class EcoAddressDashboardController extends Controller
{
    public function __construct(
        private EcoAddressDashboardCRUDService $ecoAddressService,
        private UpdateEcoAddressDashboardHandler $updateEcoAddressHandler,
        private DeleteEcoAddressDashboardHandler $deleteEcoAddressHandler,
    ) {
    }

    public function index(GetEcoAddressListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoAddressService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoAddressDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }
    public function show(GetEcoAddressDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoAddressService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoAddressDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoAddressDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoAddressService->create($request->createCreateEcoAddressDTO());

        $presenter = new EcoAddressDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoAddressDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoAddressCommand();
        $this->updateEcoAddressHandler->handle($command);

        $item = $this->ecoAddressService->get($command->getId());

        $presenter = new EcoAddressDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteEcoAddressDashboardRequest $request): JsonResponse
    {
        $this->deleteEcoAddressHandler->handle(Uuid::fromString($request->route('id')));

        return Json::success();
    }

    public function export(ExportEcoAddressDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_address.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoAddressExport($this->ecoAddressService, $filters), $fileName);
    }
}

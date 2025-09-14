<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAddress\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAddress\Handlers\DeleteEcoAddressHandler;
use Modules\Ecommerce\EcoAddress\Handlers\UpdateEcoAddressHandler;
use Modules\Ecommerce\EcoAddress\Presenters\EcoAddressPresenter;
use Modules\Ecommerce\EcoAddress\Requests\CreateEcoAddressRequest;
use Modules\Ecommerce\EcoAddress\Requests\DeleteEcoAddressRequest;
use Modules\Ecommerce\EcoAddress\Requests\GetEcoAddressListRequest;
use Modules\Ecommerce\EcoAddress\Requests\GetEcoAddressRequest;
use Modules\Ecommerce\EcoAddress\Requests\UpdateEcoAddressRequest;
use Modules\Ecommerce\EcoAddress\Services\EcoAddressCRUDService;
use Modules\Ecommerce\EcoAddress\Exports\EcoAddressExport;
use Modules\Ecommerce\EcoAddress\Requests\ExportEcoAddressRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoAddressController extends Controller
{
    public function __construct(
        private EcoAddressCRUDService $ecoAddressService,
        private UpdateEcoAddressHandler $updateEcoAddressHandler,
        private DeleteEcoAddressHandler $deleteEcoAddressHandler,
    ) {
    }

    public function index(GetEcoAddressListRequest $request): JsonResponse
    {
        $list = $this->ecoAddressService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoAddressPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoAddressRequest $request): JsonResponse
    {
        $item = $this->ecoAddressService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoAddressPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoAddressRequest $request): JsonResponse
    {
        $createdItem = $this->ecoAddressService->create($request->createCreateEcoAddressDTO());

        $presenter = new EcoAddressPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoAddressRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoAddressCommand();
        $this->updateEcoAddressHandler->handle($command);

        $item = $this->ecoAddressService->get($command->getId());

        $presenter = new EcoAddressPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoAddressRequest $request): JsonResponse
    {
        $this->deleteEcoAddressHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoaddress to a file
     *
     * @param ExportEcoAddressRequest $request
     */
    public function export(ExportEcoAddressRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_address.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoAddressExport($this->ecoAddressService, $filters), $fileName);
    }
}

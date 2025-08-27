<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoClient\Handlers\DeleteEcoClientHandler;
use Modules\Ecommerce\EcoClient\Handlers\UpdateEcoClientHandler;
use Modules\Ecommerce\EcoClient\Presenters\EcoClientPresenter;
use Modules\Ecommerce\EcoClient\Requests\CreateEcoClientRequest;
use Modules\Ecommerce\EcoClient\Requests\DeleteEcoClientRequest;
use Modules\Ecommerce\EcoClient\Requests\GetEcoClientListRequest;
use Modules\Ecommerce\EcoClient\Requests\GetEcoClientRequest;
use Modules\Ecommerce\EcoClient\Requests\UpdateEcoClientRequest;
use Modules\Ecommerce\EcoClient\Services\EcoClientCRUDService;
use Modules\Ecommerce\EcoClient\Exports\EcoClientExport;
use Modules\Ecommerce\EcoClient\Requests\ExportEcoClientRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoClientController extends Controller
{
    public function __construct(
        private EcoClientCRUDService $ecoClientService,
        private UpdateEcoClientHandler $updateEcoClientHandler,
        private DeleteEcoClientHandler $deleteEcoClientHandler,
    ) {
    }

    public function index(GetEcoClientListRequest $request): JsonResponse
    {
        $list = $this->ecoClientService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoClientPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoClientRequest $request): JsonResponse
    {
        $item = $this->ecoClientService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoClientPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoClientRequest $request): JsonResponse
    {
        $createdItem = $this->ecoClientService->create($request->createCreateEcoClientDTO());

        $presenter = new EcoClientPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoClientRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoClientCommand();
        $this->updateEcoClientHandler->handle($command);

        $item = $this->ecoClientService->get($command->getId());

        $presenter = new EcoClientPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoClientRequest $request): JsonResponse
    {
        $this->deleteEcoClientHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoclient to a file
     *
     * @param ExportEcoClientRequest $request
     */
    public function export(ExportEcoClientRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_client.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoClientExport($this->ecoClientService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoComplaint\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoComplaint\Handlers\DeleteEcoComplaintHandler;
use Modules\Ecommerce\EcoComplaint\Handlers\UpdateEcoComplaintHandler;
use Modules\Ecommerce\EcoComplaint\Presenters\EcoComplaintPresenter;
use Modules\Ecommerce\EcoComplaint\Requests\CreateEcoComplaintRequest;
use Modules\Ecommerce\EcoComplaint\Requests\DeleteEcoComplaintRequest;
use Modules\Ecommerce\EcoComplaint\Requests\GetEcoComplaintListRequest;
use Modules\Ecommerce\EcoComplaint\Requests\GetEcoComplaintRequest;
use Modules\Ecommerce\EcoComplaint\Requests\UpdateEcoComplaintRequest;
use Modules\Ecommerce\EcoComplaint\Services\EcoComplaintCRUDService;
use Modules\Ecommerce\EcoComplaint\Exports\EcoComplaintExport;
use Modules\Ecommerce\EcoComplaint\Requests\ExportEcoComplaintRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoComplaintController extends Controller
{
    public function __construct(
        private EcoComplaintCRUDService $ecoComplaintService,
        private UpdateEcoComplaintHandler $updateEcoComplaintHandler,
        private DeleteEcoComplaintHandler $deleteEcoComplaintHandler,
    ) {
    }

    public function index(GetEcoComplaintListRequest $request): JsonResponse
    {
        $list = $this->ecoComplaintService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoComplaintPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoComplaintRequest $request): JsonResponse
    {
        $item = $this->ecoComplaintService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoComplaintPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoComplaintRequest $request): JsonResponse
    {
        $createdItem = $this->ecoComplaintService->create($request->createCreateEcoComplaintDTO());

        $presenter = new EcoComplaintPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoComplaintRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoComplaintCommand();
        $this->updateEcoComplaintHandler->handle($command);

        $item = $this->ecoComplaintService->get($command->getId());

        $presenter = new EcoComplaintPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoComplaintRequest $request): JsonResponse
    {
        $this->deleteEcoComplaintHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecocomplaint to a file
     *
     * @param ExportEcoComplaintRequest $request
     */
    public function export(ExportEcoComplaintRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_complaint.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoComplaintExport($this->ecoComplaintService, $filters), $fileName);
    }
}

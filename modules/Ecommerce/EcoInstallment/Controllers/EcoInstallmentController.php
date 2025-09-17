<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoInstallment\Handlers\DeleteEcoInstallmentHandler;
use Modules\Ecommerce\EcoInstallment\Handlers\UpdateEcoInstallmentHandler;
use Modules\Ecommerce\EcoInstallment\Presenters\EcoInstallmentPresenter;
use Modules\Ecommerce\EcoInstallment\Requests\CreateEcoInstallmentRequest;
use Modules\Ecommerce\EcoInstallment\Requests\DeleteEcoInstallmentRequest;
use Modules\Ecommerce\EcoInstallment\Requests\GetEcoInstallmentListRequest;
use Modules\Ecommerce\EcoInstallment\Requests\GetEcoInstallmentRequest;
use Modules\Ecommerce\EcoInstallment\Requests\UpdateEcoInstallmentRequest;
use Modules\Ecommerce\EcoInstallment\Requests\UpsertEcoInstallmentRequest;
use Modules\Ecommerce\EcoInstallment\Services\EcoInstallmentCRUDService;
use Modules\Ecommerce\EcoInstallment\Exports\EcoInstallmentExport;
use Modules\Ecommerce\EcoInstallment\Requests\ExportEcoInstallmentRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoInstallmentController extends Controller
{
    public function __construct(
        private EcoInstallmentCRUDService $ecoInstallmentService,
        private UpdateEcoInstallmentHandler $updateEcoInstallmentHandler,
        private DeleteEcoInstallmentHandler $deleteEcoInstallmentHandler,
    ) {
    }

    public function index(GetEcoInstallmentListRequest $request): JsonResponse
    {
        $list = $this->ecoInstallmentService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoInstallmentPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoInstallmentRequest $request): JsonResponse
    {
        $item = $this->ecoInstallmentService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoInstallmentPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoInstallmentRequest $request): JsonResponse
    {
        $createdItem = $this->ecoInstallmentService->create($request->createCreateEcoInstallmentDTO());

        $presenter = new EcoInstallmentPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function upsert(UpsertEcoInstallmentRequest $request): JsonResponse
    {
        $dtos = $request->createUpsertEcoInstallmentDTOs();
        $results = $this->ecoInstallmentService->upsert($dtos);

        $presenters = array_map(function ($item) {
            return (new EcoInstallmentPresenter($item))->getData();
        }, $results);

        return Json::items($presenters);
    }

    public function update(UpdateEcoInstallmentRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoInstallmentCommand();
        $this->updateEcoInstallmentHandler->handle($command);

        $item = $this->ecoInstallmentService->get($command->getId());

        $presenter = new EcoInstallmentPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoInstallmentRequest $request): JsonResponse
    {
        $this->deleteEcoInstallmentHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoinstallment to a file
     *
     * @param ExportEcoInstallmentRequest $request
     */
    public function export(ExportEcoInstallmentRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_installment.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoInstallmentExport($this->ecoInstallmentService, $filters), $fileName);
    }
}

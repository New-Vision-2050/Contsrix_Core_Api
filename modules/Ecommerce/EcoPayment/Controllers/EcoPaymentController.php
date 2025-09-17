<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoPayment\Handlers\DeleteEcoPaymentHandler;
use Modules\Ecommerce\EcoPayment\Handlers\UpdateEcoPaymentHandler;
use Modules\Ecommerce\EcoPayment\Presenters\EcoPaymentPresenter;
use Modules\Ecommerce\EcoPayment\Requests\CreateEcoPaymentRequest;
use Modules\Ecommerce\EcoPayment\Requests\DeleteEcoPaymentRequest;
use Modules\Ecommerce\EcoPayment\Requests\GetEcoPaymentListRequest;
use Modules\Ecommerce\EcoPayment\Requests\GetEcoPaymentRequest;
use Modules\Ecommerce\EcoPayment\Requests\UpdateEcoPaymentRequest;
use Modules\Ecommerce\EcoPayment\Requests\UpsertEcoPaymentRequest;
use Modules\Ecommerce\EcoPayment\Services\EcoPaymentCRUDService;
use Modules\Ecommerce\EcoPayment\Exports\EcoPaymentExport;
use Modules\Ecommerce\EcoPayment\Requests\ExportEcoPaymentRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoPaymentController extends Controller
{
    public function __construct(
        private EcoPaymentCRUDService $ecoPaymentService,
        private UpdateEcoPaymentHandler $updateEcoPaymentHandler,
        private DeleteEcoPaymentHandler $deleteEcoPaymentHandler,
    ) {
    }

    public function index(GetEcoPaymentListRequest $request): JsonResponse
    {
        $list = $this->ecoPaymentService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoPaymentPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoPaymentRequest $request): JsonResponse
    {
        $item = $this->ecoPaymentService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoPaymentPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoPaymentRequest $request): JsonResponse
    {
        $createdItem = $this->ecoPaymentService->create($request->createCreateEcoPaymentDTO());

        $presenter = new EcoPaymentPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function upsert(UpsertEcoPaymentRequest $request): JsonResponse
    {
        $dtos = $request->createUpsertEcoPaymentDTOs();
        $results = $this->ecoPaymentService->upsert($dtos);

        $presenters = array_map(function ($item) {
            return (new EcoPaymentPresenter($item))->getData();
        }, $results);

        return Json::items($presenters);
    }

    public function update(UpdateEcoPaymentRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoPaymentCommand();
        $this->updateEcoPaymentHandler->handle($command);

        $item = $this->ecoPaymentService->get($command->getId());

        $presenter = new EcoPaymentPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoPaymentRequest $request): JsonResponse
    {
        $this->deleteEcoPaymentHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecopayment to a file
     *
     * @param ExportEcoPaymentRequest $request
     */
    public function export(ExportEcoPaymentRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_payment.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoPaymentExport($this->ecoPaymentService, $filters), $fileName);
    }
}

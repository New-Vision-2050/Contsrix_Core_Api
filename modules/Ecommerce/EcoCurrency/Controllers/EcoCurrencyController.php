<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoCurrency\Handlers\DeleteEcoCurrencyHandler;
use Modules\Ecommerce\EcoCurrency\Presenters\EcoCurrencyPresenter;
use Modules\Ecommerce\EcoCurrency\Requests\DeleteEcoCurrencyRequest;
use Modules\Ecommerce\EcoCurrency\Requests\GetEcoCurrencyListRequest;
use Modules\Ecommerce\EcoCurrency\Requests\GetEcoCurrencyRequest;
use Modules\Ecommerce\EcoCurrency\Requests\UpsertEcoCurrencyRequest;
use Modules\Ecommerce\EcoCurrency\Services\EcoCurrencyCRUDService;
use Modules\Ecommerce\EcoCurrency\Exports\EcoCurrencyExport;
use Modules\Ecommerce\EcoCurrency\Requests\ExportEcoCurrencyRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoCurrencyController extends Controller
{
    public function __construct(
        private EcoCurrencyCRUDService $ecoCurrencyService,
        private DeleteEcoCurrencyHandler $deleteEcoCurrencyHandler,
    ) {
    }

    public function index(GetEcoCurrencyListRequest $request): JsonResponse
    {
        $list = $this->ecoCurrencyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoCurrencyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoCurrencyRequest $request): JsonResponse
    {
        $item = $this->ecoCurrencyService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoCurrencyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsert(UpsertEcoCurrencyRequest $request): JsonResponse
    {
        $upsertedItems = $this->ecoCurrencyService->upsert($request->createUpsertEcoCurrencyDTO());

        $presentedItems = $upsertedItems->map(function ($item) {
            return (new EcoCurrencyPresenter($item))->getData();
        });

        return Json::items($presentedItems->toArray());
    }

    public function delete(DeleteEcoCurrencyRequest $request): JsonResponse
    {
        $this->deleteEcoCurrencyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecocurrency to a file
     *
     * @param ExportEcoCurrencyRequest $request
     */
    public function export(ExportEcoCurrencyRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_currency.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoCurrencyExport($this->ecoCurrencyService, $filters), $fileName);
    }
}

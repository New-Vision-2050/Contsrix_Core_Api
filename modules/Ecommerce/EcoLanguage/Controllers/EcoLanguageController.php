<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoLanguage\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoLanguage\Handlers\DeleteEcoLanguageHandler;
use Modules\Ecommerce\EcoLanguage\Presenters\EcoLanguagePresenter;
use Modules\Ecommerce\EcoLanguage\Requests\DeleteEcoLanguageRequest;
use Modules\Ecommerce\EcoLanguage\Requests\GetEcoLanguageListRequest;
use Modules\Ecommerce\EcoLanguage\Requests\GetEcoLanguageRequest;
use Modules\Ecommerce\EcoLanguage\Requests\UpsertEcoLanguageRequest;
use Modules\Ecommerce\EcoLanguage\Services\EcoLanguageCRUDService;
use Modules\Ecommerce\EcoLanguage\Exports\EcoLanguageExport;
use Modules\Ecommerce\EcoLanguage\Requests\ExportEcoLanguageRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoLanguageController extends Controller
{
    public function __construct(
        private EcoLanguageCRUDService $ecoLanguageService,
        private DeleteEcoLanguageHandler $deleteEcoLanguageHandler,
    ) {
    }

    public function index(GetEcoLanguageListRequest $request)//: JsonResponse
    {
        $list = $this->ecoLanguageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoLanguagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoLanguageRequest $request): JsonResponse
    {
        $item = $this->ecoLanguageService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoLanguagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsert(UpsertEcoLanguageRequest $request): JsonResponse
    {
        $upsertedItems = $this->ecoLanguageService->upsert($request->createUpsertEcoLanguageDTO());

        $presentedItems = $upsertedItems->map(function ($item) {
            return (new EcoLanguagePresenter($item))->getData();
        });

        return Json::items($presentedItems->toArray());
    }

    public function delete(DeleteEcoLanguageRequest $request): JsonResponse
    {
        $this->deleteEcoLanguageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecolanguage to a file
     *
     * @param ExportEcoLanguageRequest $request
     */
    public function export(ExportEcoLanguageRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_language.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoLanguageExport($this->ecoLanguageService, $filters), $fileName);
    }
}

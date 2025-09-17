<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBusinessActivity\Handlers\DeleteEcoBusinessActivityHandler;
use Modules\Ecommerce\EcoBusinessActivity\Handlers\UpdateEcoBusinessActivityHandler;
use Modules\Ecommerce\EcoBusinessActivity\Presenters\EcoBusinessActivityPresenter;
use Modules\Ecommerce\EcoBusinessActivity\Requests\CreateEcoBusinessActivityRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\DeleteEcoBusinessActivityRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\GetEcoBusinessActivityListRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\GetEcoBusinessActivityRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\UpdateEcoBusinessActivityRequest;
use Modules\Ecommerce\EcoBusinessActivity\Services\EcoBusinessActivityCRUDService;
use Modules\Ecommerce\EcoBusinessActivity\Exports\EcoBusinessActivityExport;
use Modules\Ecommerce\EcoBusinessActivity\Requests\ExportEcoBusinessActivityRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoBusinessActivityController extends Controller
{
    public function __construct(
        private EcoBusinessActivityCRUDService $ecoBusinessActivityService,
        private UpdateEcoBusinessActivityHandler $updateEcoBusinessActivityHandler,
        private DeleteEcoBusinessActivityHandler $deleteEcoBusinessActivityHandler,
    ) {
    }

    public function show(GetEcoBusinessActivityRequest $request): JsonResponse
    {
        $item = $this->ecoBusinessActivityService->get(Uuid::fromString(tenant("id")));

        $presenter = new EcoBusinessActivityPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBusinessActivityRequest $request): JsonResponse
    {
        $createdItem = $this->ecoBusinessActivityService->upsert($request->createCreateEcoBusinessActivityDTO());

        $presenter = new EcoBusinessActivityPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    /**
     * Export ecobusinessactivity to a file
     *
     * @param ExportEcoBusinessActivityRequest $request
     */
    public function export(ExportEcoBusinessActivityRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_business_activity.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoBusinessActivityExport($this->ecoBusinessActivityService, $filters), $fileName);
    }
}

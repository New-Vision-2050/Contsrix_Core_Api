<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoBusinessActivity\Exports\EcoBusinessActivityExport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoBusinessActivity\Handlers\Dashboard\DeleteEcoBusinessActivityDashboardHandler;
use Modules\Ecommerce\EcoBusinessActivity\Handlers\Dashboard\UpdateEcoBusinessActivityDashboardHandler;
use Modules\Ecommerce\EcoBusinessActivity\Presenters\Dashboard\EcoBusinessActivityDashboardPresenter;
use Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard\CreateEcoBusinessActivityDashboardRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard\ExportEcoBusinessActivityDashboardRequest;
use Modules\Ecommerce\EcoBusinessActivity\Requests\Dashboard\GetEcoBusinessActivityDashboardRequest;
use Modules\Ecommerce\EcoBusinessActivity\Services\Dashboard\EcoBusinessActivityCRUDDashboardService;
use Ramsey\Uuid\Uuid;

class EcoBusinessActivityDashboardController extends Controller
{
    public function __construct(
        private EcoBusinessActivityCRUDDashboardService $ecoBusinessActivityService,
        private UpdateEcoBusinessActivityDashboardHandler $updateEcoBusinessActivityHandler,
        private DeleteEcoBusinessActivityDashboardHandler $deleteEcoBusinessActivityHandler,
    ) {
    }

    public function show(GetEcoBusinessActivityDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoBusinessActivityService->get(Uuid::fromString(tenant("id")));

        $presenter = new EcoBusinessActivityDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoBusinessActivityDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoBusinessActivityService->upsert($request->createCreateEcoBusinessActivityDTO());

        $presenter = new EcoBusinessActivityDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function export(ExportEcoBusinessActivityDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_business_activity.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new EcoBusinessActivityExport($this->ecoBusinessActivityService, $filters), $fileName);
    }
}

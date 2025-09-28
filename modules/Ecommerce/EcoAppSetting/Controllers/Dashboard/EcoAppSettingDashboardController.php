<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Controllers\Dashboard;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAppSetting\Presenters\Dashboard\EcoAppSettingDashboardPresenter;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\CreateEcoAppSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpdateEcoAppSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\GetEcoAppSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\GetEcoAppSettingListDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\DeleteEcoAppSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\ExportEcoAppSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Services\Dashboard\EcoAppSettingDashboardCRUDService;
use Modules\Ecommerce\EcoAppSetting\Handlers\Dashboard\UpdateEcoAppSettingDashboardHandler;
use Modules\Ecommerce\EcoAppSetting\Handlers\Dashboard\DeleteEcoAppSettingDashboardHandler;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoAppSetting\Exports\EcoAppSettingExport;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoAppSettingFrontPageDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoAppSettingThemeDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoBannerSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoCartSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoFavoritesSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoFilterDisplaySettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoFilterSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoProductCardSettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoProductDisplaySettingDashboardRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\Dashboard\UpsertEcoTermsSettingDashboardRequest;
use Ramsey\Uuid\Uuid;

class EcoAppSettingDashboardController extends Controller
{
    public function __construct(
        private EcoAppSettingDashboardCRUDService $ecoAppSettingService,
        private UpdateEcoAppSettingDashboardHandler $updateEcoAppSettingHandler,
        private DeleteEcoAppSettingDashboardHandler $deleteEcoAppSettingHandler,
    ) {
    }

    public function index(GetEcoAppSettingListDashboardRequest $request): JsonResponse
    {
        $list = $this->ecoAppSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoAppSettingDashboardPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoAppSettingDashboardRequest $request): JsonResponse
    {
        $item = $this->ecoAppSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoAppSettingDashboardRequest $request): JsonResponse
    {
        $createdItem = $this->ecoAppSettingService->create($request->createCreateEcoAppSettingDTO());

        $presenter = new EcoAppSettingDashboardPresenter($createdItem);

        return Json::item($presenter->getData());
    }


    public function export(ExportEcoAppSettingDashboardRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_app_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoAppSettingExport($this->ecoAppSettingService, $filters), $fileName);
    }

    public function upsertTheme(UpsertEcoAppSettingThemeDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoAppSettingThemeDTO();
        $item = $this->ecoAppSettingService->upsertTheme($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getByCompany(string $companyId): JsonResponse
    {
        $item = $this->ecoAppSettingService->getByCompany($companyId);

        if (!$item) {
            return Json::error('App settings not found for this company', 404);
        }

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertFrontPage(UpsertEcoAppSettingFrontPageDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoAppSettingFrontPageDTO();
        $item = $this->ecoAppSettingService->upsertFrontPage($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertBanner(UpsertEcoBannerSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoBannerSettingDTO();
        $item = $this->ecoBannerSettingService->upsert($command);

        $presenter = new EcoBannerSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getBannerByCompany(): JsonResponse
    {
        $companyId = tenant("id");
        $item = $this->ecoBannerSettingService->getByCompany($companyId);

        if (!$item) {
            return Json::error('Banner settings not found for this company', 404);
        }

        $presenter = new EcoBannerSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertProductDisplay(UpsertEcoProductDisplaySettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoProductDisplaySettingDTO();
        $item = $this->ecoAppSettingService->upsertProductDisplay($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertFavorites(UpsertEcoFavoritesSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoFavoritesSettingDTO();
        $item = $this->ecoAppSettingService->upsertFavorites($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertFilters(UpsertEcoFilterSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoFilterSettingDTO();
        $items = $this->ecoFilterSettingService->upsert($command);
        return Json::item(EcoFilterSettingDashboardPresenter::collection($items));

    }

    public function getFiltersByCompany(): JsonResponse
    {
        $companyId = tenant("id");
        $items = $this->ecoFilterSettingService->getByCompany($companyId);

        if ($items->isEmpty()) {
            return Json::error('Filter settings not found for this company', 404);
        }

        $data = EcoFilterSettingDashboardPresenter::collection($items);

        return Json::item(['filters' => $data]);
    }

    public function upsertProductCard(UpsertEcoProductCardSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoProductCardSettingDTO();
        $item = $this->ecoAppSettingService->upsertProductCard($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertFilterDisplay(UpsertEcoFilterDisplaySettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoFilterDisplaySettingDTO();
        $item = $this->ecoAppSettingService->upsertFilterDisplay($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertTerms(UpsertEcoTermsSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoTermsSettingDTO();
        $item = $this->ecoAppSettingService->upsertTerms($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertCart(UpsertEcoCartSettingDashboardRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoCartSettingDTO();
        $item = $this->ecoAppSettingService->upsertCart($command);

        $presenter = new EcoAppSettingDashboardPresenter($item);

        return Json::item($presenter->getData());
    }
}
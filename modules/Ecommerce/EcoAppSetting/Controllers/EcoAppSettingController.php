<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoAppSetting\Handlers\DeleteEcoAppSettingHandler;
use Modules\Ecommerce\EcoAppSetting\Handlers\UpdateEcoAppSettingHandler;
use Modules\Ecommerce\EcoAppSetting\Presenters\EcoAppSettingPresenter;
use Modules\Ecommerce\EcoAppSetting\Requests\CreateEcoAppSettingRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\DeleteEcoAppSettingRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\GetEcoAppSettingListRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\GetEcoAppSettingRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\UpdateEcoAppSettingRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\UpsertEcoAppSettingThemeRequest;
use Modules\Ecommerce\EcoAppSetting\DTO\UpsertEcoAppSettingThemeDTO;
use Modules\Ecommerce\EcoAppSetting\Requests\UpsertEcoAppSettingFrontPageRequest;
use Modules\Ecommerce\EcoAppSetting\Requests\UpsertEcoBannerSettingRequest;
use Modules\Ecommerce\EcoAppSetting\Services\EcoBannerSettingCRUDService;
use Modules\Ecommerce\EcoAppSetting\Presenters\EcoBannerSettingPresenter;
use Modules\Ecommerce\EcoAppSetting\Services\EcoAppSettingCRUDService;
use Modules\Ecommerce\EcoAppSetting\Exports\EcoAppSettingExport;
use Modules\Ecommerce\EcoAppSetting\Requests\ExportEcoAppSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class EcoAppSettingController extends Controller
{
    public function __construct(
        private EcoAppSettingCRUDService $ecoAppSettingService,
        private UpdateEcoAppSettingHandler $updateEcoAppSettingHandler,
        private DeleteEcoAppSettingHandler $deleteEcoAppSettingHandler,
        private EcoBannerSettingCRUDService $ecoBannerSettingService,
    ) {
    }

    public function index(GetEcoAppSettingListRequest $request): JsonResponse
    {
        $list = $this->ecoAppSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(EcoAppSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetEcoAppSettingRequest $request): JsonResponse
    {
        $item = $this->ecoAppSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new EcoAppSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateEcoAppSettingRequest $request): JsonResponse
    {
        $createdItem = $this->ecoAppSettingService->create($request->createCreateEcoAppSettingDTO());

        $presenter = new EcoAppSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateEcoAppSettingRequest $request): JsonResponse
    {
        $command = $request->createUpdateEcoAppSettingCommand();
        $this->updateEcoAppSettingHandler->handle($command);

        $item = $this->ecoAppSettingService->get($command->getId());

        $presenter = new EcoAppSettingPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteEcoAppSettingRequest $request): JsonResponse
    {
        $this->deleteEcoAppSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export ecoappsetting to a file
     *
     * @param ExportEcoAppSettingRequest $request
     */
    public function export(ExportEcoAppSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'eco_app_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new EcoAppSettingExport($this->ecoAppSettingService, $filters), $fileName);
    }

    public function upsertTheme(UpsertEcoAppSettingThemeRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoAppSettingThemeDTO();
        $item = $this->ecoAppSettingService->upsertTheme($command);

        $presenter = new EcoAppSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getByCompany(string $companyId): JsonResponse
    {
        $item = $this->ecoAppSettingService->getByCompany($companyId);

        if (!$item) {
            return Json::error('App settings not found for this company', 404);
        }

        $presenter = new EcoAppSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertFrontPage(UpsertEcoAppSettingFrontPageRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoAppSettingFrontPageDTO();
        $item = $this->ecoAppSettingService->upsertFrontPage($command);

        $presenter = new EcoAppSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function upsertBanner(UpsertEcoBannerSettingRequest $request): JsonResponse
    {
        $command = $request->createUpsertEcoBannerSettingDTO();
        $item = $this->ecoBannerSettingService->upsert($command);

        $presenter = new EcoBannerSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getBannerByCompany(): JsonResponse
    {
        $companyId = tenant("id");
        $item = $this->ecoBannerSettingService->getByCompany($companyId);

        if (!$item) {
            return Json::error('Banner settings not found for this company', 404);
        }

        $presenter = new EcoBannerSettingPresenter($item);

        return Json::item($presenter->getData());
    }
}

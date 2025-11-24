<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePageSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Handlers\DeleteWebsiteHomePageSettingHandler;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Handlers\UpdateWebsiteHomePageSettingHandler;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Presenters\WebsiteHomePageSettingPresenter;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\CreateWebsiteHomePageSettingRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\DeleteWebsiteHomePageSettingRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\GetWebsiteHomePageSettingListRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\GetWebsiteHomePageSettingRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\UpdateWebsiteHomePageSettingRequest;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Services\WebsiteHomePageSettingCRUDService;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Exports\WebsiteHomePageSettingExport;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Requests\ExportWebsiteHomePageSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteHomePageSettingController extends Controller
{
    public function __construct(
        private WebsiteHomePageSettingCRUDService $websiteHomePageSettingService,
        private UpdateWebsiteHomePageSettingHandler $updateWebsiteHomePageSettingHandler,
        private DeleteWebsiteHomePageSettingHandler $deleteWebsiteHomePageSettingHandler,
    ) {
    }

    public function index(GetWebsiteHomePageSettingListRequest $request): JsonResponse
    {
        $list = $this->websiteHomePageSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteHomePageSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteHomePageSettingRequest $request)
    {
        $item = $this->websiteHomePageSettingService->getCurrentCompanySetting();

        if (!$item) {
            return Json::error('No home page setting found for current company', 404);
        }

        $presenter = new WebsiteHomePageSettingPresenter($item);

        return Json::item($presenter->getData());
    }



    public function update(UpdateWebsiteHomePageSettingRequest $request): JsonResponse
    {
        $updatedItem = $this->websiteHomePageSettingService->updateCurrentCompanySetting($request->toDTO());

        $presenter = new WebsiteHomePageSettingPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteHomePageSettingRequest $request): JsonResponse
    {
        $this->deleteWebsiteHomePageSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websitehomepagesetting to a file
     *
     * @param ExportWebsiteHomePageSettingRequest $request
     */
    public function export(ExportWebsiteHomePageSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_home_page_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteHomePageSettingExport($this->websiteHomePageSettingService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteSetting\Handlers\DeleteWebsiteSettingHandler;
use Modules\WebsiteCMS\WebsiteSetting\Presenters\WebsiteSettingPresenter;
use Modules\WebsiteCMS\WebsiteSetting\Requests\CreateWebsiteSettingRequest;
use Modules\WebsiteCMS\WebsiteSetting\Requests\DeleteWebsiteSettingRequest;
use Modules\WebsiteCMS\WebsiteSetting\Requests\GetWebsiteSettingListRequest;
use Modules\WebsiteCMS\WebsiteSetting\Requests\GetWebsiteSettingRequest;
use Modules\WebsiteCMS\WebsiteSetting\Requests\UpdateWebsiteSettingRequest;
use Modules\WebsiteCMS\WebsiteSetting\Requests\UpdateCurrentWebsiteSettingRequest;
use Modules\WebsiteCMS\WebsiteSetting\Services\WebsiteSettingCRUDService;
use Modules\WebsiteCMS\WebsiteSetting\Exports\WebsiteSettingExport;
use Modules\WebsiteCMS\WebsiteSetting\Requests\ExportWebsiteSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteSettingController extends Controller
{
    public function __construct(
        private WebsiteSettingCRUDService $websiteSettingService,
        private DeleteWebsiteSettingHandler $deleteWebsiteSettingHandler,
    ) {
    }

    public function index(GetWebsiteSettingListRequest $request): JsonResponse
    {
        $list = $this->websiteSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteSettingRequest $request): JsonResponse
    {
        $item = $this->websiteSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function current(): JsonResponse
    {
        $item = $this->websiteSettingService->getForCurrentCompany();

        $presenter = new WebsiteSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function updateCurrent(UpdateCurrentWebsiteSettingRequest $request)
    {
        $data = [
            'main_color' => $request->getMainColor(),
            'second_color' => $request->getSecondColor(),
            'background_color' => $request->getBackgroundColor(),
            'website_address' => $request->getWebsiteAddress(),
        ];

        // Remove null values

        $updatedItem = $this->websiteSettingService->updateForCurrentCompany(
            $data,
            $request->getLogo()
        );

        $presenter = new WebsiteSettingPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteSettingRequest $request): JsonResponse
    {
        $createdItem = $this->websiteSettingService->create($request->createCreateWebsiteSettingDTO());

        $presenter = new WebsiteSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteSettingRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteSettingCommand();
        $updatedItem = $this->websiteSettingService->update($command);

        $presenter = new WebsiteSettingPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteSettingRequest $request): JsonResponse
    {
        $this->deleteWebsiteSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websitesetting to a file
     *
     * @param ExportWebsiteSettingRequest $request
     */
    public function export(ExportWebsiteSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteSettingExport($this->websiteSettingService, $filters), $fileName);
    }
}

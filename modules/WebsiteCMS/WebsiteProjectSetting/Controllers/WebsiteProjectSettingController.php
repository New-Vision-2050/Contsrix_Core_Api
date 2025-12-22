<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteProjectSetting\Handlers\DeleteWebsiteProjectSettingHandler;
use Modules\WebsiteCMS\WebsiteProjectSetting\Handlers\UpdateWebsiteProjectSettingHandler;
use Modules\WebsiteCMS\WebsiteProjectSetting\Presenters\WebsiteProjectSettingPresenter;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\CreateWebsiteProjectSettingRequest;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\DeleteWebsiteProjectSettingRequest;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\GetWebsiteProjectSettingListRequest;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\GetWebsiteProjectSettingRequest;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\UpdateWebsiteProjectSettingRequest;
use Modules\WebsiteCMS\WebsiteProjectSetting\Services\WebsiteProjectSettingCRUDService;
use Modules\WebsiteCMS\WebsiteProjectSetting\Exports\WebsiteProjectSettingExport;
use Modules\WebsiteCMS\WebsiteProjectSetting\Requests\ExportWebsiteProjectSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteProjectSettingController extends Controller
{
    public function __construct(
        private WebsiteProjectSettingCRUDService $websiteProjectSettingService,
        private UpdateWebsiteProjectSettingHandler $updateWebsiteProjectSettingHandler,
        private DeleteWebsiteProjectSettingHandler $deleteWebsiteProjectSettingHandler,
    ) {
    }

    public function index(GetWebsiteProjectSettingListRequest $request): JsonResponse
    {
        $list = $this->websiteProjectSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteProjectSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteProjectSettingRequest $request): JsonResponse
    {
        $item = $this->websiteProjectSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteProjectSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteProjectSettingRequest $request): JsonResponse
    {
        $createdItem = $this->websiteProjectSettingService->create($request->createCreateWebsiteProjectSettingDTO());

        $presenter = new WebsiteProjectSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteProjectSettingRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteProjectSettingCommand();
        $this->updateWebsiteProjectSettingHandler->handle($command);

        $item = $this->websiteProjectSettingService->get($command->getId());

        $presenter = new WebsiteProjectSettingPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteProjectSettingRequest $request): JsonResponse
    {
        $this->deleteWebsiteProjectSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function all(): JsonResponse
    {
        $list = $this->websiteProjectSettingService->getAll();

        return Json::items(WebsiteProjectSettingPresenter::collection($list));
    }

    /**
     * Export websiteprojectsetting to a file
     *
     * @param ExportWebsiteProjectSettingRequest $request
     */
    public function export(ExportWebsiteProjectSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_project_setting.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new WebsiteProjectSettingExport($this->websiteProjectSettingService, $filters), $fileName);
    }
}

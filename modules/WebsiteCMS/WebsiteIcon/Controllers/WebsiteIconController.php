<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteIcon\Handlers\DeleteWebsiteIconHandler;
use Modules\WebsiteCMS\WebsiteIcon\Handlers\UpdateWebsiteIconHandler;
use Modules\WebsiteCMS\WebsiteIcon\Presenters\WebsiteIconPresenter;
use Modules\WebsiteCMS\WebsiteIcon\Requests\CreateWebsiteIconRequest;
use Modules\WebsiteCMS\WebsiteIcon\Requests\DeleteWebsiteIconRequest;
use Modules\WebsiteCMS\WebsiteIcon\Requests\GetWebsiteIconListRequest;
use Modules\WebsiteCMS\WebsiteIcon\Requests\GetWebsiteIconRequest;
use Modules\WebsiteCMS\WebsiteIcon\Requests\UpdateWebsiteIconRequest;
use Modules\WebsiteCMS\WebsiteIcon\Services\WebsiteIconCRUDService;
use Modules\WebsiteCMS\WebsiteIcon\Exports\WebsiteIconExport;
use Modules\WebsiteCMS\WebsiteIcon\Requests\ExportWebsiteIconRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteIconController extends Controller
{
    public function __construct(
        private WebsiteIconCRUDService $websiteIconService,
        private UpdateWebsiteIconHandler $updateWebsiteIconHandler,
        private DeleteWebsiteIconHandler $deleteWebsiteIconHandler,
    ) {
    }

    public function index(GetWebsiteIconListRequest $request): JsonResponse
    {
        $list = $this->websiteIconService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteIconPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteIconRequest $request): JsonResponse
    {
        $item = $this->websiteIconService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteIconPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteIconRequest $request): JsonResponse
    {
        $createdItem = $this->websiteIconService->create($request->createCreateWebsiteIconDTO());

        $presenter = new WebsiteIconPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteIconRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteIconCommand();
        $this->updateWebsiteIconHandler->handle($command);

        $item = $this->websiteIconService->get($command->getId());

        $presenter = new WebsiteIconPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteIconRequest $request): JsonResponse
    {
        $this->deleteWebsiteIconHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websiteicon to a file
     *
     * @param ExportWebsiteIconRequest $request
     */
    public function export(ExportWebsiteIconRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_icon.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new WebsiteIconExport($this->websiteIconService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteNews\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteNews\Handlers\DeleteWebsiteNewsHandler;
use Modules\WebsiteCMS\WebsiteNews\Handlers\UpdateWebsiteNewsHandler;
use Modules\WebsiteCMS\WebsiteNews\Presenters\WebsiteNewsPresenter;
use Modules\WebsiteCMS\WebsiteNews\Requests\CreateWebsiteNewsRequest;
use Modules\WebsiteCMS\WebsiteNews\Requests\DeleteWebsiteNewsRequest;
use Modules\WebsiteCMS\WebsiteNews\Requests\GetWebsiteNewsListRequest;
use Modules\WebsiteCMS\WebsiteNews\Requests\GetWebsiteNewsRequest;
use Modules\WebsiteCMS\WebsiteNews\Requests\UpdateWebsiteNewsRequest;
use Modules\WebsiteCMS\WebsiteNews\Services\WebsiteNewsCRUDService;
use Modules\WebsiteCMS\WebsiteNews\Exports\WebsiteNewsExport;
use Modules\WebsiteCMS\WebsiteNews\Requests\ExportWebsiteNewsRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteNewsController extends Controller
{
    public function __construct(
        private WebsiteNewsCRUDService $websiteNewsService,
        private UpdateWebsiteNewsHandler $updateWebsiteNewsHandler,
        private DeleteWebsiteNewsHandler $deleteWebsiteNewsHandler,
    ) {
    }

    public function index(GetWebsiteNewsListRequest $request): JsonResponse
    {
        $list = $this->websiteNewsService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteNewsPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteNewsRequest $request): JsonResponse
    {
        $item = $this->websiteNewsService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteNewsPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteNewsRequest $request): JsonResponse
    {
        $createdItem = $this->websiteNewsService->create($request->createCreateWebsiteNewsDTO());

        $presenter = new WebsiteNewsPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteNewsRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteNewsCommand();
        $this->updateWebsiteNewsHandler->handle($command);

        $item = $this->websiteNewsService->get($command->getId());

        $presenter = new WebsiteNewsPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteNewsRequest $request): JsonResponse
    {
        $this->deleteWebsiteNewsHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function updateStatus(GetWebsiteNewsRequest $request): JsonResponse
    {
        $item = $this->websiteNewsService->toggleStatus(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteNewsPresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Export websitenews to a file
     *
     * @param ExportWebsiteNewsRequest $request
     */
    public function export(ExportWebsiteNewsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_news.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteNewsExport($this->websiteNewsService, $filters), $fileName);
    }
}

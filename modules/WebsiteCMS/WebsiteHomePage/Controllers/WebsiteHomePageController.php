<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteHomePage\Handlers\DeleteWebsiteHomePageHandler;
use Modules\WebsiteCMS\WebsiteHomePage\Handlers\UpdateWebsiteHomePageHandler;
use Modules\WebsiteCMS\WebsiteHomePage\Presenters\WebsiteHomePagePresenter;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\CreateWebsiteHomePageRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\DeleteWebsiteHomePageRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\GetWebsiteHomePageListRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\GetWebsiteHomePageRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\UpdateWebsiteHomePageRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageCRUDService;
use Modules\WebsiteCMS\WebsiteHomePage\Services\WebsiteHomePageService;
use Modules\WebsiteCMS\WebsiteHomePage\Exports\WebsiteHomePageExport;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\ExportWebsiteHomePageRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Requests\GetHomePageDataRequest;
use Modules\WebsiteCMS\WebsiteHomePage\Presenters\HomePageDataPresenter;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteHomePageController extends Controller
{
    public function __construct(
        private WebsiteHomePageCRUDService   $websiteHomePageService,
        private WebsiteHomePageService       $homePageService,
        private UpdateWebsiteHomePageHandler $updateWebsiteHomePageHandler,
        private DeleteWebsiteHomePageHandler $deleteWebsiteHomePageHandler,
    )
    {
    }

    public function index(GetWebsiteHomePageListRequest $request): JsonResponse
    {
        $list = $this->websiteHomePageService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(WebsiteHomePagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteHomePageRequest $request): JsonResponse
    {
        $item = $this->websiteHomePageService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteHomePagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteHomePageRequest $request): JsonResponse
    {
        $createdItem = $this->websiteHomePageService->create($request->createCreateWebsiteHomePageDTO());

        $presenter = new WebsiteHomePagePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteHomePageRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteHomePageCommand();
        $this->updateWebsiteHomePageHandler->handle($command);

        $item = $this->websiteHomePageService->get($command->getId());

        $presenter = new WebsiteHomePagePresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteHomePageRequest $request): JsonResponse
    {
        $this->deleteWebsiteHomePageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getHomePageData(GetHomePageDataRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        $data = $this->homePageService->getHomePageData($dto->limit);


       return Json::item((new HomePageDataPresenter($data))->getData());

    }

    /**
     * Export websitehomepage to a file
     *
     * @param ExportWebsiteHomePageRequest $request
     */
    public function export(ExportWebsiteHomePageRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_home_page.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteHomePageExport($this->websiteHomePageService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteProject\Handlers\DeleteWebsiteProjectHandler;
use Modules\WebsiteCMS\WebsiteProject\Handlers\UpdateWebsiteProjectHandler;
use Modules\WebsiteCMS\WebsiteProject\Presenters\WebsiteProjectPresenter;
use Modules\WebsiteCMS\WebsiteProject\Requests\CreateWebsiteProjectRequest;
use Modules\WebsiteCMS\WebsiteProject\Requests\DeleteWebsiteProjectRequest;
use Modules\WebsiteCMS\WebsiteProject\Requests\DeleteMediaRequest;
use Modules\WebsiteCMS\WebsiteProject\Requests\GetWebsiteProjectListRequest;
use Modules\WebsiteCMS\WebsiteProject\Requests\GetWebsiteProjectRequest;
use Modules\WebsiteCMS\WebsiteProject\Requests\UpdateWebsiteProjectRequest;
use Modules\WebsiteCMS\WebsiteProject\Services\WebsiteProjectCRUDService;
use Modules\WebsiteCMS\WebsiteProject\Exports\WebsiteProjectExport;
use Modules\WebsiteCMS\WebsiteProject\Requests\ExportWebsiteProjectRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteProjectController extends Controller
{
    public function __construct(
        private WebsiteProjectCRUDService $websiteProjectService,
        private UpdateWebsiteProjectHandler $updateWebsiteProjectHandler,
        private DeleteWebsiteProjectHandler $deleteWebsiteProjectHandler,
    ) {
    }

    public function index(GetWebsiteProjectListRequest $request): JsonResponse
    {
        $list = $this->websiteProjectService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteProjectPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteProjectRequest $request): JsonResponse
    {
        $item = $this->websiteProjectService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteProjectPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteProjectRequest $request): JsonResponse
    {
        $createdItem = $this->websiteProjectService->create($request->createCreateWebsiteProjectDTO());

        $presenter = new WebsiteProjectPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteProjectRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteProjectCommand();
        $this->updateWebsiteProjectHandler->handle($command);

        $item = $this->websiteProjectService->get($command->getId());

        $presenter = new WebsiteProjectPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteProjectRequest $request): JsonResponse
    {
        $this->deleteWebsiteProjectHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websiteproject to a file
     *
     * @param ExportWebsiteProjectRequest $request
     */
    public function export(ExportWebsiteProjectRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_project.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteProjectExport($this->websiteProjectService, $filters), $fileName);
    }

    public function deleteMedia(DeleteMediaRequest $request): JsonResponse
    {
        $projectId = Uuid::fromString($request->route('id'));
        $mediaId = (int) $request->route('media_id');

        $this->websiteProjectService->deleteMedia($projectId, $mediaId);

        return Json::success(__('Media deleted successfully'));
    }
}

<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAboutUs\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteAboutUs\Handlers\DeleteWebsiteAboutUsHandler;
use Modules\WebsiteCMS\WebsiteAboutUs\Handlers\UpdateWebsiteAboutUsHandler;
use Modules\WebsiteCMS\WebsiteAboutUs\Presenters\WebsiteAboutUsPresenter;
use Modules\WebsiteCMS\WebsiteAboutUs\Presenters\WebsiteAboutUsWebsitePresenter;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\CreateWebsiteAboutUsRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\DeleteWebsiteAboutUsRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\GetWebsiteAboutUsListRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\GetWebsiteAboutUsRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\UpdateWebsiteAboutUsRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\UpdateCurrentCompanyAboutUsRequest;
use Modules\WebsiteCMS\WebsiteAboutUs\Services\WebsiteAboutUsCRUDService;
use Modules\WebsiteCMS\WebsiteAboutUs\Exports\WebsiteAboutUsExport;
use Modules\WebsiteCMS\WebsiteAboutUs\Requests\ExportWebsiteAboutUsRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteAboutUsController extends Controller
{
    public function __construct(
        private WebsiteAboutUsCRUDService $websiteAboutUsService,
        private UpdateWebsiteAboutUsHandler $updateWebsiteAboutUsHandler,
        private DeleteWebsiteAboutUsHandler $deleteWebsiteAboutUsHandler,
    ) {
    }

    public function index(GetWebsiteAboutUsListRequest $request): JsonResponse
    {
        $list = $this->websiteAboutUsService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteAboutUsPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteAboutUsRequest $request): JsonResponse
    {
        $item = $this->websiteAboutUsService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteAboutUsPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteAboutUsRequest $request): JsonResponse
    {
        $createdItem = $this->websiteAboutUsService->create($request->createCreateWebsiteAboutUsDTO());

        $presenter = new WebsiteAboutUsPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteAboutUsRequest $request): JsonResponse
    {
        $updatedItem = $this->websiteAboutUsService->update($request->createUpdateWebsiteAboutUsDTO());

        $presenter = new WebsiteAboutUsPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteAboutUsRequest $request): JsonResponse
    {
        $this->websiteAboutUsService->delete(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websiteaboutus to a file
     *
     * @param ExportWebsiteAboutUsRequest $request
     */
    public function export(ExportWebsiteAboutUsRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_about_us.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteAboutUsExport($this->websiteAboutUsService, $filters), $fileName);
    }

    /**
     * Get current company's about us
     */
    public function getCurrentCompanyAboutUs(): JsonResponse
    {
        $item = $this->websiteAboutUsService->getCurrentCompanyAboutUs();

        if (!$item) {
            return Json::error('About us not found for current company', 404);
        }

        $presenter = new WebsiteAboutUsPresenter($item);

        return Json::item($presenter->getData());
    }


    /**
     * Get current company's about us
     */
    public function getCurrentAboutUsWebsite(): JsonResponse
    {
        $item = $this->websiteAboutUsService->getCurrentCompanyAboutUs();

        if (!$item) {
            return Json::error('About us not found for current company', 404);
        }

        $presenter = new WebsiteAboutUsWebsitePresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Update current company's about us
     */
    public function updateCurrentCompanyAboutUs(UpdateCurrentCompanyAboutUsRequest $request)
    {
        $updatedItem = $this->websiteAboutUsService->updateCurrentCompanyAboutUs($request->createUpdateWebsiteAboutUsDTO());

        $presenter = new WebsiteAboutUsPresenter($updatedItem);

        return Json::item($presenter->getData());
    }
}

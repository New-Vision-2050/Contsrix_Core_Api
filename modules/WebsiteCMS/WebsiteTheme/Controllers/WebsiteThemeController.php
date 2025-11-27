<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTheme\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteTheme\Handlers\DeleteWebsiteThemeHandler;
use Modules\WebsiteCMS\WebsiteTheme\Handlers\UpdateWebsiteThemeHandler;
use Modules\WebsiteCMS\WebsiteTheme\Presenters\WebsiteThemePresenter;
use Modules\WebsiteCMS\WebsiteTheme\Requests\CreateWebsiteThemeRequest;
use Modules\WebsiteCMS\WebsiteTheme\Requests\DeleteWebsiteThemeRequest;
use Modules\WebsiteCMS\WebsiteTheme\Requests\GetWebsiteThemeListRequest;
use Modules\WebsiteCMS\WebsiteTheme\Requests\GetWebsiteThemeRequest;
use Modules\WebsiteCMS\WebsiteTheme\Requests\UpdateWebsiteThemeRequest;
use Modules\WebsiteCMS\WebsiteTheme\Services\WebsiteThemeCRUDService;
use Modules\WebsiteCMS\WebsiteTheme\Exports\WebsiteThemeExport;
use Modules\WebsiteCMS\WebsiteTheme\Requests\ExportWebsiteThemeRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteThemeController extends Controller
{
    public function __construct(
        private WebsiteThemeCRUDService $websiteThemeService,
        private UpdateWebsiteThemeHandler $updateWebsiteThemeHandler,
        private DeleteWebsiteThemeHandler $deleteWebsiteThemeHandler,
    ) {
    }

    public function index(GetWebsiteThemeListRequest $request): JsonResponse
    {
        $list = $this->websiteThemeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteThemePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteThemeRequest $request): JsonResponse
    {
        $item = $this->websiteThemeService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteThemePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteThemeRequest $request): JsonResponse
    {
        $createdItem = $this->websiteThemeService->create($request->createCreateWebsiteThemeDTO());

        $presenter = new WebsiteThemePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteThemeRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteThemeCommand();
        $this->updateWebsiteThemeHandler->handle($command);

        $item = $this->websiteThemeService->get($command->getId());

        $presenter = new WebsiteThemePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteThemeRequest $request): JsonResponse
    {
        $this->deleteWebsiteThemeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websitetheme to a file
     *
     * @param ExportWebsiteThemeRequest $request
     */
    public function export(ExportWebsiteThemeRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_theme.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new WebsiteThemeExport($this->websiteThemeService, $filters), $fileName);
    }

    /**
     * Get the website theme for the current company
     */
    public function getCurrentCompanyTheme(GetWebsiteThemeRequest $request): JsonResponse
    {
        $theme = $this->websiteThemeService->getCurrentCompanyTheme();

        if (!$theme) {
            return Json::error('No theme found for the current company', 404);
        }

        $presenter = new WebsiteThemePresenter($theme);

        return Json::item($presenter->getData());
    }

    /**
     * Update the website theme for the current company
     */
    public function updateCurrentCompanyTheme(UpdateWebsiteThemeRequest $request): JsonResponse
    {
        $theme = $this->websiteThemeService->updateCurrentCompanyTheme($request->toDTO());

        $presenter = new WebsiteThemePresenter($theme);

        return Json::item($presenter->getData());
    }
}

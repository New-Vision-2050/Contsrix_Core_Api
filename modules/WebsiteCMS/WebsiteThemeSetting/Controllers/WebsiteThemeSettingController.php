<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteThemeSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteThemeSetting\Presenters\WebsiteThemeSettingPresenter;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\CreateWebsiteThemeSettingRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\DeleteWebsiteThemeSettingRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\GetWebsiteThemeSettingListRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\GetWebsiteThemeSettingRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\UpdateWebsiteThemeSettingRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\AssignThemeToCompanyRequest;
use Modules\WebsiteCMS\WebsiteThemeSetting\Services\WebsiteThemeSettingCRUDService;
use Modules\WebsiteCMS\WebsiteThemeSetting\Exports\WebsiteThemeSettingExport;
use Modules\WebsiteCMS\WebsiteThemeSetting\Requests\ExportWebsiteThemeSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteThemeSettingController extends Controller
{
    public function __construct(
        private WebsiteThemeSettingCRUDService $websiteThemeSettingService,
    ) {
    }

    public function index(GetWebsiteThemeSettingListRequest $request): JsonResponse
    {
        $list = $this->websiteThemeSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteThemeSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteThemeSettingRequest $request): JsonResponse
    {
        $item = $this->websiteThemeSettingService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteThemeSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteThemeSettingRequest $request): JsonResponse
    {
        $createdItem = $this->websiteThemeSettingService->create($request->createCreateWebsiteThemeSettingDTO());

        $presenter = new WebsiteThemeSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteThemeSettingRequest $request): JsonResponse
    {
        $id = Uuid::fromString($request->route('id'));
        $updatedItem = $this->websiteThemeSettingService->update($id, $request->toDTO());

        $presenter = new WebsiteThemeSettingPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteThemeSettingRequest $request): JsonResponse
    {
        $this->websiteThemeSettingService->delete(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Assign theme setting to a company
     */
    public function assignThemeToCompany(AssignThemeToCompanyRequest $request): JsonResponse
    {
        $this->websiteThemeSettingService->assignThemeToCompany($request->toDTO());

        return Json::success( 'Theme setting assigned to company successfully');
    }

    /**
     * Get theme setting for a specific company
     */
    public function getCompanyThemeSetting(): JsonResponse
    {
        $themeSetting = $this->websiteThemeSettingService->getCompanyThemeSetting(
            Uuid::fromString(tenant("id"))
        );

        if (!$themeSetting) {
            return Json::error(['message' => 'No theme setting assigned to this company'], 404);
        }

        $presenter = new WebsiteThemeSettingPresenter($themeSetting);

        return Json::item($presenter->getData());
    }

    /**
     * Get default theme setting
     */
    public function getDefaultThemeSetting(): JsonResponse
    {
        $themeSetting = $this->websiteThemeSettingService->getDefaultThemeSetting();

        if (!$themeSetting) {
            return Json::error(['message' => 'No default theme setting found'], 404);
        }

        $presenter = new WebsiteThemeSettingPresenter($themeSetting);

        return Json::item($presenter->getData());
    }


}

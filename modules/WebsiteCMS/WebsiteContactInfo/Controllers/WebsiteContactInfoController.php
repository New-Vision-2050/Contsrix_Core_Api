<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteContactInfo\Handlers\DeleteWebsiteContactInfoHandler;
use Modules\WebsiteCMS\WebsiteContactInfo\Handlers\UpdateWebsiteContactInfoHandler;
use Modules\WebsiteCMS\WebsiteContactInfo\Presenters\WebsiteContactInfoPresenter;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\CreateWebsiteContactInfoRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\DeleteWebsiteContactInfoRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\GetWebsiteContactInfoListRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\GetWebsiteContactInfoRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\UpdateWebsiteContactInfoRequest;
use Modules\WebsiteCMS\WebsiteContactInfo\Services\WebsiteContactInfoCRUDService;
use Modules\WebsiteCMS\WebsiteContactInfo\Exports\WebsiteContactInfoExport;
use Modules\WebsiteCMS\WebsiteContactInfo\Requests\ExportWebsiteContactInfoRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteContactInfoController extends Controller
{
    public function __construct(
        private WebsiteContactInfoCRUDService $websiteContactInfoService,
        private UpdateWebsiteContactInfoHandler $updateWebsiteContactInfoHandler,
        private DeleteWebsiteContactInfoHandler $deleteWebsiteContactInfoHandler,
    ) {
    }

    public function index(GetWebsiteContactInfoListRequest $request): JsonResponse
    {
        $list = $this->websiteContactInfoService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteContactInfoPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteContactInfoRequest $request): JsonResponse
    {
        $item = $this->websiteContactInfoService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteContactInfoPresenter($item);

        return Json::item($presenter->getData());
    }







    /**
     * Export websitecontactinfo to a file
     *
     * @param ExportWebsiteContactInfoRequest $request
     */
    public function export(ExportWebsiteContactInfoRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_contact_info.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteContactInfoExport($this->websiteContactInfoService, $filters), $fileName);
    }

    /**
     * Get current company contact info
     */
    public function getCurrentCompanyContactInfo(): JsonResponse
    {
        $contactInfo = $this->websiteContactInfoService->getCurrentCompanyContactInfo();

        if (!$contactInfo) {
            return Json::error('Contact info not found for current company', 404);
        }

        $presenter = new WebsiteContactInfoPresenter($contactInfo);

        return Json::item($presenter->getData());
    }

    /**
     * Update current company contact info
     */
    public function updateCurrentCompanyContactInfo(UpdateWebsiteContactInfoRequest $request): JsonResponse
    {
        $contactInfo = $this->websiteContactInfoService->updateCurrentCompanyContactInfo($request->toDTO());

        $presenter = new WebsiteContactInfoPresenter($contactInfo);

        return Json::item($presenter->getData());
    }
}

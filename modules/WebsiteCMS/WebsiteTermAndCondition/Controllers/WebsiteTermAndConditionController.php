<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers\DeleteWebsiteTermAndConditionHandler;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers\UpdateWebsiteTermAndConditionHandler;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Presenters\WebsiteTermAndConditionPresenter;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\CreateWebsiteTermAndConditionRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\DeleteWebsiteTermAndConditionRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\GetWebsiteTermAndConditionListRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\GetWebsiteTermAndConditionRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\UpdateWebsiteTermAndConditionForCurrentCompanyRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\UpdateWebsiteTermAndConditionRequest;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Services\WebsiteTermAndConditionCRUDService;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Exports\WebsiteTermAndConditionExport;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Requests\ExportWebsiteTermAndConditionRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteTermAndConditionController extends Controller
{
    public function __construct(
        private WebsiteTermAndConditionCRUDService   $websiteTermAndConditionService,
        private UpdateWebsiteTermAndConditionHandler $updateWebsiteTermAndConditionHandler,
        private DeleteWebsiteTermAndConditionHandler $deleteWebsiteTermAndConditionHandler,
    )
    {
    }

    public function index(GetWebsiteTermAndConditionListRequest $request): JsonResponse
    {
        $list = $this->websiteTermAndConditionService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(WebsiteTermAndConditionPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteTermAndConditionRequest $request): JsonResponse
    {
        $item = $this->websiteTermAndConditionService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteTermAndConditionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function getForCurrentCompany()
    {
        $termsAndCondition = $this->websiteTermAndConditionService->getForCurrentCompany();
        return Json::item((new WebsiteTermAndConditionPresenter($termsAndCondition))->getData());
    }

    public function updateForCurrentCompany(UpdateWebsiteTermAndConditionForCurrentCompanyRequest $request): JsonResponse
    {
        $termsAndCondition = $this->websiteTermAndConditionService->updateForCurrentComapny($request->createUpdateWebsiteTermAndConditionForCurrentCompanyCommand());
        return Json::item((new WebsiteTermAndConditionPresenter($termsAndCondition))->getData());
    }



    public function update(UpdateWebsiteTermAndConditionRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteTermAndConditionCommand();
        $this->updateWebsiteTermAndConditionHandler->handle($command);

        $item = $this->websiteTermAndConditionService->get($command->getId());

        $presenter = new WebsiteTermAndConditionPresenter($item);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteWebsiteTermAndConditionRequest $request): JsonResponse
    {
        $this->deleteWebsiteTermAndConditionHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websitetermandcondition to a file
     *
     * @param ExportWebsiteTermAndConditionRequest $request
     */
    public function export(ExportWebsiteTermAndConditionRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_term_and_condition.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new WebsiteTermAndConditionExport($this->websiteTermAndConditionService, $filters), $fileName);
    }
}

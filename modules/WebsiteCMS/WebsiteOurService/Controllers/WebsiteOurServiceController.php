<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\WebsiteOurService\Handlers\DeleteWebsiteOurServiceHandler;
use Modules\WebsiteCMS\WebsiteOurService\Handlers\UpdateWebsiteOurServiceHandler;
use Modules\WebsiteCMS\WebsiteOurService\Presenters\WebsiteOurServicePresenter;
use Modules\WebsiteCMS\WebsiteOurService\Requests\CreateWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\DeleteWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\GetWebsiteOurServiceListRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\GetWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\UpdateWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\GetCurrentCompanyWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Requests\UpdateCurrentCompanyWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Services\WebsiteOurServiceCRUDService;
use Modules\WebsiteCMS\WebsiteOurService\Exports\WebsiteOurServiceExport;
use Modules\WebsiteCMS\WebsiteOurService\Requests\ExportWebsiteOurServiceRequest;
use Modules\WebsiteCMS\WebsiteOurService\Enums\ServiceTypeEnum;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class WebsiteOurServiceController extends Controller
{
    public function __construct(
        private WebsiteOurServiceCRUDService $websiteOurServiceService,
        private UpdateWebsiteOurServiceHandler $updateWebsiteOurServiceHandler,
        private DeleteWebsiteOurServiceHandler $deleteWebsiteOurServiceHandler,
    ) {
    }

    public function index(GetWebsiteOurServiceListRequest $request): JsonResponse
    {
        $list = $this->websiteOurServiceService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(WebsiteOurServicePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetWebsiteOurServiceRequest $request): JsonResponse
    {
        $item = $this->websiteOurServiceService->get(Uuid::fromString($request->route('id')));

        $presenter = new WebsiteOurServicePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateWebsiteOurServiceRequest $request): JsonResponse
    {
        $createdItem = $this->websiteOurServiceService->create($request->toDTO());

        $presenter = new WebsiteOurServicePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateWebsiteOurServiceRequest $request): JsonResponse
    {
        $command = $request->createUpdateWebsiteOurServiceCommand();
        $this->updateWebsiteOurServiceHandler->handle($command);

        $item = $this->websiteOurServiceService->get($command->getId());

        $presenter = new WebsiteOurServicePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteWebsiteOurServiceRequest $request): JsonResponse
    {
        $this->deleteWebsiteOurServiceHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export websiteourservice to a file
     *
     * @param ExportWebsiteOurServiceRequest $request
     */
    public function export(ExportWebsiteOurServiceRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'website_our_service.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new WebsiteOurServiceExport($this->websiteOurServiceService, $filters), $fileName);
    }

    /**
     * Get current company's website our service
     */
    public function getCurrentCompany(GetCurrentCompanyWebsiteOurServiceRequest $request): JsonResponse
    {
        $item = $this->websiteOurServiceService->getCurrentCompany();

        if (!$item) {
            return Json::error('No website our service found for current company', 404);
        }

        $presenter = new WebsiteOurServicePresenter($item);

        return Json::item($presenter->getData());
    }

    /**
     * Update current company's website our service
     */
    public function updateCurrentCompany(UpdateCurrentCompanyWebsiteOurServiceRequest $request): JsonResponse
    {
        $updatedItem = $this->websiteOurServiceService->updateCurrentCompany($request->toDTO());

        $presenter = new WebsiteOurServicePresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    /**
     * Get service type enum WebsiteOurServiceController as id-name pairs
     */
    public function getServiceTypes(): JsonResponse
    {
        $serviceTypes = array_map(function ($case) {
            return [
                'id' => $case->value,
                'name' => $case->label(),
            ];
        }, ServiceTypeEnum::cases());

        return Json::items($serviceTypes);
    }
}

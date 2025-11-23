<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteService\Controllers;

use App\Http\Controllers\Controller;

use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Presenters\CategoryWebsiteCMSPresenter;
use Modules\WebsiteCMS\WebsiteService\Exports\WebsiteServiceExport;
use Modules\WebsiteCMS\WebsiteService\Handlers\UpdateWebsiteServiceHandler;
use Modules\WebsiteCMS\WebsiteService\Presenters\WebsiteServicePresenter;
use Modules\WebsiteCMS\WebsiteService\Requests\CreateWebsiteServiceRequest;
use Modules\WebsiteCMS\WebsiteService\Requests\ExportWebsiteServiceRequest;
use Modules\WebsiteCMS\WebsiteService\Requests\UpdateStatusRequest;
use Modules\WebsiteCMS\WebsiteService\Requests\UpdateWebsiteServiceRequest;
use Modules\WebsiteCMS\WebsiteService\Services\WebsiteServiceCRUDService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WebsiteServiceController extends Controller
{
    public function __construct(
        private WebsiteServiceCRUDService $service,
        private UpdateWebsiteServiceHandler $updateHandler
    ) {
    }

    public function index(): JsonResponse
    {
        $filters = request()->only(['name', 'reference_number', 'category_website_cms_id']);
        $perPage = (int) request()->get('per_page', 10);
        $page = (int) request()->get('page', 1);

        $list = $this->service->list($filters, $page,$perPage);

        return Json::items(WebsiteServicePresenter::collection($list['data']), paginationSettings: $list['pagination']);

    }

    public function store(CreateWebsiteServiceRequest $request)
    {
        $service = $this->service->create($request->toDTO());


        return Json::item(
            (new WebsiteServicePresenter($service))->getData()
        );
    }

    public function show(string $id): JsonResponse
    {
        $service = $this->service->get($id);

        if (!$service) {
            return Json::error('Website service not found', 404);
        }

        return Json::item(
            (new WebsiteServicePresenter($service))->getData()
        );
    }

    public function update(UpdateWebsiteServiceRequest $request, string $id): JsonResponse
    {
        $service = $this->updateHandler->handle($request->toCommand());

        return Json::item(
            (new WebsiteServicePresenter($service))->getData()
        );
    }

    public function destroy(string $id): JsonResponse
    {
        $deleted = $this->service->delete($id);

        if (!$deleted) {
            return Json::error('Website service not found', 404);
        }

        return Json::deleted();
    }

    public function export(ExportWebsiteServiceRequest $request): BinaryFileResponse
    {
        $filters = $request->getFilters();
        $format = $filters['format'] ?? 'xlsx';

        $export = new WebsiteServiceExport($this->service, $filters);

        $fileName = 'website_services_' . now()->format('Y_m_d_His') . '.' . $format;

        return Excel::download($export, $fileName);
    }

    public function updateStatus(UpdateStatusRequest $request, string $id): JsonResponse
    {
        $service = $this->service->updateStatus($id,(int) $request->validated()['status']);

        return Json::item(
            (new WebsiteServicePresenter($service))->getData()
        );
    }
}

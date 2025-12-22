<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Handlers\DeleteCategoryWebsiteCMSHandler;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Handlers\UpdateCategoryWebsiteCMSHandler;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Presenters\CategoryWebsiteCMSPresenter;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\CreateCategoryWebsiteCMSRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\DeleteCategoryWebsiteCMSRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\GetCategoryWebsiteCMSListRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\GetCategoryWebsiteCMSRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\UpdateCategoryWebsiteCMSRequest;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Services\CategoryWebsiteCMSCRUDService;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Exports\CategoryWebsiteCMSExport;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Requests\ExportCategoryWebsiteCMSRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class CategoryWebsiteCMSController extends Controller
{
    public function __construct(
        private CategoryWebsiteCMSCRUDService $categoryWebsiteCMSService,
        private UpdateCategoryWebsiteCMSHandler $updateCategoryWebsiteCMSHandler,
        private DeleteCategoryWebsiteCMSHandler $deleteCategoryWebsiteCMSHandler,
    ) {
    }

    public function index(GetCategoryWebsiteCMSListRequest $request): JsonResponse
    {
        $list = $this->categoryWebsiteCMSService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(CategoryWebsiteCMSPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetCategoryWebsiteCMSRequest $request): JsonResponse
    {
        $item = $this->categoryWebsiteCMSService->get(Uuid::fromString($request->route('id')));

        $presenter = new CategoryWebsiteCMSPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateCategoryWebsiteCMSRequest $request): JsonResponse
    {
        $createdItem = $this->categoryWebsiteCMSService->create($request->createCreateCategoryWebsiteCMSDTO());

        $presenter = new CategoryWebsiteCMSPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateCategoryWebsiteCMSRequest $request): JsonResponse
    {
        $command = $request->createUpdateCategoryWebsiteCMSCommand();
        $this->updateCategoryWebsiteCMSHandler->handle($command);

        $item = $this->categoryWebsiteCMSService->get($command->getId());

        $presenter = new CategoryWebsiteCMSPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteCategoryWebsiteCMSRequest $request): JsonResponse
    {
        $this->deleteCategoryWebsiteCMSHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function getCetegoryTypes()
    {
        return Json::items($this->categoryWebsiteCMSService->getTypes());
    }

    public function all(): JsonResponse
    {
        $list = $this->categoryWebsiteCMSService->getAll();

        return Json::items(CategoryWebsiteCMSPresenter::collection($list));
    }

    /**
     * Export categorywebsitecms to a file
     *
     */
    public function export(ExportCategoryWebsiteCMSRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'category_website_c_m_s.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new CategoryWebsiteCMSExport($this->categoryWebsiteCMSService, $filters), $fileName);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\Page\Handlers\DeletePageHandler;
use Modules\Ecommerce\Page\Handlers\UpdatePageHandler;
use Modules\Ecommerce\Page\Presenters\PagePresenter;
use Modules\Ecommerce\Page\Requests\CreatePageRequest;
use Modules\Ecommerce\Page\Requests\DeletePageRequest;
use Modules\Ecommerce\Page\Requests\GetPageListRequest;
use Modules\Ecommerce\Page\Requests\GetPageRequest;
use Modules\Ecommerce\Page\Requests\GetPageByTypeRequest;
use Modules\Ecommerce\Page\Requests\UpsertPageByTypeRequest;
use Modules\Ecommerce\Page\Requests\UpdatePageRequest;
use Modules\Ecommerce\Page\Services\PageCRUDService;
use Modules\Ecommerce\Page\Exports\PageExport;
use Modules\Ecommerce\Page\Requests\ExportPageRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class PageController extends Controller
{
    public function __construct(
        private PageCRUDService $pageService,
        private UpdatePageHandler $updatePageHandler,
        private DeletePageHandler $deletePageHandler,
    ) {
    }

    public function index(GetPageListRequest $request): JsonResponse
    {
        $list = $this->pageService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(PagePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetPageRequest $request): JsonResponse
    {
        $item = $this->pageService->get(Uuid::fromString($request->route('id')));

        $presenter = new PagePresenter($item);

        return Json::item($presenter->getData(false)); // false = details view, not listing
    }

    public function store(CreatePageRequest $request): JsonResponse
    {
        $createdItem = $this->pageService->create($request->createCreatePageDTO());

        $presenter = new PagePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdatePageRequest $request): JsonResponse
    {
        $command = $request->createUpdatePageCommand();
        $this->updatePageHandler->handle($command);

        $item = $this->pageService->get($command->getId());

        $presenter = new PagePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeletePageRequest $request): JsonResponse
    {
        $this->deletePageHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Get page by type for current company
     */
    public function getByType( $type='home'): JsonResponse
    {
        $page = $this->pageService->getByType($type);
        
        if (!$page) {
            return Json::error('Page not found', 404);
        }

        $presenter = new PagePresenter($page);
        return Json::item($presenter->getData(false)); // Details view
    }

    /**
     * Create or update page by type (upsert)
     */
    public function upsertByType($type,UpsertPageByTypeRequest $request): JsonResponse
    {
        $type = $request->route('type');
        
        $pageData = [
            'description' => $request->input('description'),
            'type' => $type,
            'company_id' => Uuid::fromString(tenant("id")),
        ];
        $page = $this->pageService->upsertByType($type, $pageData);
        
        $presenter = new PagePresenter($page);
        return Json::item($presenter->getData(false)); // Details view
    }

    /**
     * Export page to a file
     *
     * @param ExportPageRequest $request
     */
    public function export(ExportPageRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'page.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new PageExport($this->pageService, $filters), $fileName);
    }
}

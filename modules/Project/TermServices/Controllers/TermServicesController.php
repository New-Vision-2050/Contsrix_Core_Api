<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Project\TermServices\Handlers\DeleteTermServicesHandler;
use Modules\Project\TermServices\Handlers\UpdateTermServicesHandler;
use Modules\Project\TermServices\Presenters\TermServicesPresenter;
use Modules\Project\TermServices\Requests\CreateTermServicesRequest;
use Modules\Project\TermServices\Requests\DeleteTermServicesRequest;
use Modules\Project\TermServices\Requests\GetTermServicesListRequest;
use Modules\Project\TermServices\Requests\GetTermServicesRequest;
use Modules\Project\TermServices\Requests\UpdateTermServicesRequest;
use Modules\Project\TermServices\Services\TermServicesCRUDService;
use Modules\Project\TermServices\Exports\TermServicesExport;
use Modules\Project\TermServices\Requests\ExportTermServicesRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class TermServicesController extends Controller
{
    public function __construct(
        private TermServicesCRUDService $termServicesService,
        private UpdateTermServicesHandler $updateTermServicesHandler,
        private DeleteTermServicesHandler $deleteTermServicesHandler,
    ) {
    }

    public function index(GetTermServicesListRequest $request): JsonResponse
    {
        $list = $this->termServicesService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TermServicesPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTermServicesRequest $request): JsonResponse
    {
        $item = $this->termServicesService->get(Uuid::fromString($request->route('id')));

        $presenter = new TermServicesPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTermServicesRequest $request): JsonResponse
    {
        $createdItem = $this->termServicesService->create($request->createCreateTermServicesDTO());

        $presenter = new TermServicesPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTermServicesRequest $request): JsonResponse
    {
        $command = $request->createUpdateTermServicesCommand();
        $this->updateTermServicesHandler->handle($command);

        $item = $this->termServicesService->get($command->getId());

        $presenter = new TermServicesPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTermServicesRequest $request): JsonResponse
    {
        $this->deleteTermServicesHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export termservices to a file
     *
     * @param ExportTermServicesRequest $request
     */
    public function export(ExportTermServicesRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'term_services.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new TermServicesExport($this->termServicesService, $filters), $fileName);
    }
}

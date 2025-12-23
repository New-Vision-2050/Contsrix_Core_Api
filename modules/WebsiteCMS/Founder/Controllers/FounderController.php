<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\Founder\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\WebsiteCMS\Founder\Handlers\DeleteFounderHandler;
use Modules\WebsiteCMS\Founder\Handlers\UpdateFounderHandler;
use Modules\WebsiteCMS\Founder\Presenters\FounderPresenter;
use Modules\WebsiteCMS\Founder\Requests\CreateFounderRequest;
use Modules\WebsiteCMS\Founder\Requests\DeleteFounderRequest;
use Modules\WebsiteCMS\Founder\Requests\GetFounderListRequest;
use Modules\WebsiteCMS\Founder\Requests\GetFounderRequest;
use Modules\WebsiteCMS\Founder\Requests\UpdateFounderRequest;
use Modules\WebsiteCMS\Founder\Services\FounderCRUDService;
use Modules\WebsiteCMS\Founder\Exports\FounderExport;
use Modules\WebsiteCMS\Founder\Requests\ExportFounderRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class FounderController extends Controller
{
    public function __construct(
        private FounderCRUDService $founderService,
        private UpdateFounderHandler $updateFounderHandler,
        private DeleteFounderHandler $deleteFounderHandler,
    ) {
    }

    public function index(GetFounderListRequest $request): JsonResponse
    {
        $list = $this->founderService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(FounderPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetFounderRequest $request): JsonResponse
    {
        $item = $this->founderService->get(Uuid::fromString($request->route('id')));

        $presenter = new FounderPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateFounderRequest $request): JsonResponse
    {
        $createdItem = $this->founderService->create($request->createCreateFounderDTO());

        $presenter = new FounderPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateFounderRequest $request): JsonResponse
    {
        $command = $request->createUpdateFounderCommand();
        $this->updateFounderHandler->handle($command);

        $item = $this->founderService->get($command->getId());

        $presenter = new FounderPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteFounderRequest $request): JsonResponse
    {
        $this->deleteFounderHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    public function toggleStatus(GetFounderRequest $request): JsonResponse
    {
        $founderId = Uuid::fromString($request->route('id'));
        $updatedFounder = $this->founderService->toggleStatus($founderId);
        
        $presenter = new FounderPresenter($updatedFounder);
        
        return Json::item($presenter->getData());
    }

    /**
     * Export founder to a file
     *
     * @param ExportFounderRequest $request
     */
    public function export(ExportFounderRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'founder.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new FounderExport($this->founderService, $filters), $fileName);
    }
}

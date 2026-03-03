<?php

declare(strict_types=1);

namespace Modules\TermServiceSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\TermServiceSetting\Handlers\DeleteTermServiceSettingHandler;
use Modules\TermServiceSetting\Presenters\TermServiceSettingPresenter;
use Modules\TermServiceSetting\Requests\CreateTermServiceSettingRequest;
use Modules\TermServiceSetting\Requests\DeleteTermServiceSettingRequest;
use Modules\TermServiceSetting\Requests\GetTermServiceSettingListRequest;
use Modules\TermServiceSetting\Requests\GetTermServiceSettingRequest;
use Modules\TermServiceSetting\Requests\UpdateTermServiceSettingRequest;
use Modules\TermServiceSetting\Services\TermServiceSettingCRUDService;
use Modules\TermServiceSetting\Exports\TermServiceSettingExport;
use Modules\TermServiceSetting\Requests\ExportTermServiceSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
class TermServiceSettingController extends Controller
{
    public function __construct(
        private TermServiceSettingCRUDService $termServiceSettingService,
        private DeleteTermServiceSettingHandler $deleteTermServiceSettingHandler,
    ) {
    }

    public function index(GetTermServiceSettingListRequest $request): JsonResponse
    {
        $list = $this->termServiceSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TermServiceSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTermServiceSettingRequest $request): JsonResponse
    {
        $item = $this->termServiceSettingService->get((int) $request->route('id'));

        $presenter = new TermServiceSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTermServiceSettingRequest $request): JsonResponse
    {
        $createdItem = $this->termServiceSettingService->create($request->createCreateTermServiceSettingDTO());

        $presenter = new TermServiceSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTermServiceSettingRequest $request): JsonResponse
    {
        $updatedItem = $this->termServiceSettingService->update($request->createUpdateTermServiceSettingDTO());

        $presenter = new TermServiceSettingPresenter($updatedItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteTermServiceSettingRequest $request): JsonResponse
    {
        $this->deleteTermServiceSettingHandler->handle((int) $request->route('id'));

        return Json::deleted();
    }

    /**
     * Export termservicesetting to a file
     *
     * @param ExportTermServiceSettingRequest $request
     */
    public function export(ExportTermServiceSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'term_service_setting.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new TermServiceSettingExport($this->termServiceSettingService, $filters), $fileName);
    }

    public function getAll(): JsonResponse
    {
        $items = $this->termServiceSettingService->getAll();

        $presentedItems = [];
        foreach ($items as $item) {
            $presenter = new TermServiceSettingPresenter($item);
            $presentedItems[] = $presenter->getData();
        }

        return Json::items($presentedItems);
    }
}

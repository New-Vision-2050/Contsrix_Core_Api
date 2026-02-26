<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Project\TermSetting\Handlers\DeleteTermSettingHandler;
use Modules\Project\TermSetting\Handlers\UpdateTermSettingHandler;
use Modules\Project\TermSetting\Presenters\TermSettingPresenter;
use Modules\Project\TermSetting\Requests\CreateTermSettingRequest;
use Modules\Project\TermSetting\Requests\DeleteTermSettingRequest;
use Modules\Project\TermSetting\Requests\GetTermSettingListRequest;
use Modules\Project\TermSetting\Requests\GetTermSettingRequest;
use Modules\Project\TermSetting\Requests\UpdateTermSettingRequest;
use Modules\Project\TermSetting\Services\TermSettingCRUDService;
use Modules\Project\TermSetting\Exports\TermSettingExport;
use Modules\Project\TermSetting\Requests\ExportTermSettingRequest;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Project\TermSetting\Requests\GetTermSettingChildrenRequest;
use Modules\Project\TermSetting\Presenters\TermSettingTreePresenter;

class TermSettingController extends Controller
{
    public function __construct(
        private TermSettingCRUDService $termSettingService,
        private UpdateTermSettingHandler $updateTermSettingHandler,
        private DeleteTermSettingHandler $deleteTermSettingHandler,
    ) {
    }

    public function index(GetTermSettingListRequest $request): JsonResponse
    {
        $list = $this->termSettingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(TermSettingPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetTermSettingRequest $request): JsonResponse
    {
        $item = $this->termSettingService->get((int) $request->route('id'));

        $presenter = new TermSettingPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateTermSettingRequest $request): JsonResponse
    {
        $createdItem = $this->termSettingService->create($request->createCreateTermSettingDTO());

        $presenter = new TermSettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateTermSettingRequest $request): JsonResponse
    {
        $command = $request->createUpdateTermSettingCommand();
        $this->updateTermSettingHandler->handle($command);

        $item = $this->termSettingService->get($command->getId());

        $presenter = new TermSettingPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteTermSettingRequest $request): JsonResponse
    {
        $this->deleteTermSettingHandler->handle((int) $request->route('id'));

        return Json::deleted();
    }

    /**
     * Export termsetting to a file
     *
     * @param ExportTermSettingRequest $request
     */
    public function export(ExportTermSettingRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'term_setting.' . $format;
        $filters = $request->getFilters();

        return Excel::download(new TermSettingExport($this->termSettingService, $filters), $fileName);
    }

    public function getChildren(GetTermSettingChildrenRequest $request): JsonResponse
    {
        $children = $this->termSettingService->getChildren((int) $request->route('id'));

        return Json::items(TermSettingPresenter::collection($children));
    }

    public function getTree(): JsonResponse
    {
        $tree = $this->termSettingService->getTermsTree();

        return Json::items(TermSettingTreePresenter::collection($tree));
    }
}

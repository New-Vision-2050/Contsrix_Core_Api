<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Leave\LeavePolicy\Handlers\DeleteLeavePolicyHandler;
use Modules\Leave\LeavePolicy\Handlers\UpdateLeavePolicyHandler;
use Modules\Leave\LeavePolicy\Presenters\LeavePolicyPresenter;
use Modules\Leave\LeavePolicy\Requests\CreateLeavePolicyRequest;
use Modules\Leave\LeavePolicy\Requests\DeleteLeavePolicyRequest;
use Modules\Leave\LeavePolicy\Requests\GetLeavePolicyListRequest;
use Modules\Leave\LeavePolicy\Requests\GetLeavePolicyRequest;
use Modules\Leave\LeavePolicy\Requests\UpdateLeavePolicyRequest;
use Modules\Leave\LeavePolicy\Services\LeavePolicyCRUDService;
use Modules\Leave\LeavePolicy\Exports\LeavePolicyExport;
use Modules\Leave\LeavePolicy\Requests\ExportLeavePolicyRequest;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;

class LeavePolicyController extends Controller
{
    public function __construct(
        private LeavePolicyCRUDService $leavePolicyService,
        private UpdateLeavePolicyHandler $updateLeavePolicyHandler,
        private DeleteLeavePolicyHandler $deleteLeavePolicyHandler,
    ) {
    }

    public function index(GetLeavePolicyListRequest $request): JsonResponse
    {
        $list = $this->leavePolicyService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(LeavePolicyPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetLeavePolicyRequest $request): JsonResponse
    {
        $item = $this->leavePolicyService->get(Uuid::fromString($request->route('id')));

        $presenter = new LeavePolicyPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateLeavePolicyRequest $request): JsonResponse
    {
        $createdItem = $this->leavePolicyService->create($request->createCreateLeavePolicyDTO());

        $presenter = new LeavePolicyPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateLeavePolicyRequest $request): JsonResponse
    {
        $command = $request->createUpdateLeavePolicyCommand();
        $this->updateLeavePolicyHandler->handle($command);

        $item = $this->leavePolicyService->get($command->getId());

        $presenter = new LeavePolicyPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteLeavePolicyRequest $request): JsonResponse
    {
        $this->deleteLeavePolicyHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }

    /**
     * Export leave policies to a file
     *
     * @param ExportLeavePolicyRequest $request
     */
    public function export(ExportLeavePolicyRequest $request)
    {
        $format = $request->get('format', 'xlsx');
        $fileName = 'leave_policies.' . $format;
        $filters = $request->getFilters();
        
        return Excel::download(new LeavePolicyExport($this->leavePolicyService, $filters), $fileName);
    }
}

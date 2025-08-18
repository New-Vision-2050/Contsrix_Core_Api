<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Leave\LeaveType\Handlers\DeleteLeaveTypeHandler;
use Modules\Leave\LeaveType\Handlers\UpdateLeaveTypeHandler;
use Modules\Leave\LeaveType\Presenters\LeaveTypePresenter;
use Modules\Leave\LeaveType\Requests\CreateLeaveTypeRequest;
use Modules\Leave\LeaveType\Requests\DeleteLeaveTypeRequest;
use Modules\Leave\LeaveType\Requests\GetLeaveTypeListRequest;
use Modules\Leave\LeaveType\Requests\GetLeaveTypeRequest;
use Modules\Leave\LeaveType\Requests\UpdateLeaveTypeRequest;
use Modules\Leave\LeaveType\Services\LeaveTypeCRUDService;
use Ramsey\Uuid\Uuid;

class LeaveTypeController extends Controller
{
    public function __construct(
        private LeaveTypeCRUDService $leaveTypeService,
        private UpdateLeaveTypeHandler $updateLeaveTypeHandler,
        private DeleteLeaveTypeHandler $deleteLeaveTypeHandler,
    ) {
    }

    public function index(GetLeaveTypeListRequest $request): JsonResponse
    {
        $list = $this->leaveTypeService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(LeaveTypePresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetLeaveTypeRequest $request): JsonResponse
    {
        $item = $this->leaveTypeService->get(Uuid::fromString($request->route('id')));

        $presenter = new LeaveTypePresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateLeaveTypeRequest $request): JsonResponse
    {
        $createdItem = $this->leaveTypeService->create($request->createCreateLeaveTypeDTO());

        $presenter = new LeaveTypePresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateLeaveTypeRequest $request): JsonResponse
    {
        $command = $request->createUpdateLeaveTypeCommand();
        $this->updateLeaveTypeHandler->handle($command);

        $item = $this->leaveTypeService->get($command->getId());

        $presenter = new LeaveTypePresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteLeaveTypeRequest $request): JsonResponse
    {
        $this->deleteLeaveTypeHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}

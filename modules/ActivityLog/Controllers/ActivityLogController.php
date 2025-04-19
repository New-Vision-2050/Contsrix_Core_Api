<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\ActivityLog\Handlers\DeleteActivityLogHandler;
use Modules\ActivityLog\Handlers\UpdateActivityLogHandler;
use Modules\ActivityLog\Presenters\ActivityLogPresenter;
use Modules\ActivityLog\Requests\CreateActivityLogRequest;
use Modules\ActivityLog\Requests\DeleteActivityLogRequest;
use Modules\ActivityLog\Requests\GetActivityLogListRequest;
use Modules\ActivityLog\Requests\GetActivityLogRequest;
use Modules\ActivityLog\Requests\UpdateActivityLogRequest;
use Modules\ActivityLog\Services\ActivityLogCRUDService;
use Ramsey\Uuid\Uuid;

class ActivityLogController extends Controller
{
    public function __construct(
        private ActivityLogCRUDService $activityLogService,
        private UpdateActivityLogHandler $updateActivityLogHandler,
        private DeleteActivityLogHandler $deleteActivityLogHandler,
    ) {
    }

    public function index(GetActivityLogListRequest $request): JsonResponse
    {
        $list = $this->activityLogService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::items(ActivityLogPresenter::collection($list['data']), paginationSettings: $list['pagination']);
    }

    public function show(GetActivityLogRequest $request): JsonResponse
    {
        $item = $this->activityLogService->get(Uuid::fromString($request->route('id')));

        $presenter = new ActivityLogPresenter($item);

        return Json::item($presenter->getData());
    }

    public function store(CreateActivityLogRequest $request): JsonResponse
    {
        $createdItem = $this->activityLogService->create($request->createCreateActivityLogDTO());

        $presenter = new ActivityLogPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function update(UpdateActivityLogRequest $request): JsonResponse
    {
        $command = $request->createUpdateActivityLogCommand();
        $this->updateActivityLogHandler->handle($command);

        $item = $this->activityLogService->get($command->getId());

        $presenter = new ActivityLogPresenter($item);

        return Json::item( $presenter->getData());
    }

    public function delete(DeleteActivityLogRequest $request): JsonResponse
    {
        $this->deleteActivityLogHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}

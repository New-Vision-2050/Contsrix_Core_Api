<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Handlers\DeleteSettingHandler;
use Modules\Setting\Handlers\UpdateSettingHandler;
use Modules\Setting\Presenters\SettingPresenter;
use Modules\Setting\Requests\Controllers\CreateSettingRequest;
use Modules\Setting\Requests\Controllers\DeleteSettingRequest;
use Modules\Setting\Requests\Controllers\GetSettingListRequest;
use Modules\Setting\Requests\Controllers\GetSettingRequest;
use Modules\Setting\Requests\Controllers\UpdateSettingRequest;
use Modules\Setting\Services\SettingCRUDService;
use Ramsey\Uuid\Uuid;

class QuestionSettingController extends Controller
{
    public function __construct(
        private SettingCRUDService $settingService,

    ) {
    }

    public function index(GetSettingListRequest $request): JsonResponse
    {
        $list = $this->settingService->list(
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return Json::buildItems('settings', SettingPresenter::collection($list));
    }

    public function show(GetSettingRequest $request): JsonResponse
    {
        $item = $this->settingService->get(Uuid::fromString($request->route('id')));

        $presenter = new SettingPresenter($item);

        return Json::buildItems('setting', $presenter->getData());
    }

    public function store(CreateSettingRequest $request): JsonResponse
    {
        $createdItem = $this->settingService->create($request->createCreateSettingDTO());

        $presenter = new SettingPresenter($createdItem);

        return Json::buildItems('setting', $presenter->getData());
    }

    public function update(UpdateSettingRequest $request): JsonResponse
    {
        $command = $request->createUpdateSettingCommand();
        $this->updateSettingHandler->handle($command);

        $item = $this->settingService->get($command->getId());

        $presenter = new SettingPresenter($item);

        return Json::buildItems('setting', $presenter->getData());
    }

    public function delete(DeleteSettingRequest $request): JsonResponse
    {
        $this->deleteSettingHandler->handle(Uuid::fromString($request->route('id')));

        return Json::deleted();
    }
}

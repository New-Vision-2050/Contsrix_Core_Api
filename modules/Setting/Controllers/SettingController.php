<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Handlers\DeleteSettingHandler;
use Modules\Setting\Presenters\SettingPresenter;
use Modules\Setting\Requests\CreateSettingRequest;
use Modules\Setting\Requests\DeleteSettingRequest;
use Modules\Setting\Requests\GetSettingListRequest;
use Modules\Setting\Services\SettingCRUDService;

class SettingController extends Controller
{
    public function __construct(
        private SettingCRUDService $settingService,
        private DeleteSettingHandler $deleteSettingHandler,
    ) {
    }

    public function index(GetSettingListRequest $request): JsonResponse
    {
        $list = $this->settingService->all();
        return Json::Items( SettingPresenter::collection($list['data'],paginationSettings: $list['pagination']));
    }

    public function store(CreateSettingRequest $request): JsonResponse
    {
        $createdItem = $this->settingService->create($request->createCreateSettingDTO());

        $presenter = new SettingPresenter($createdItem);

        return Json::item($presenter->getData());
    }

    public function delete(DeleteSettingRequest $request): JsonResponse
    {
        $this->deleteSettingHandler->handle($request->get('key'));

        return Json::deleted();
    }
}

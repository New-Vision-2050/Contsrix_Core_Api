<?php

declare(strict_types=1);

namespace Modules\Setting\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Setting\Handlers\DeleteSettingHandler;
use Modules\Setting\Handlers\MakeIdentifierSettingDefaultHandler;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Presenters\IdentifierPresenter;
use Modules\Setting\Presenters\LoginWayPresenter;
use Modules\Setting\Presenters\SettingPresenter;
use Modules\Setting\Requests\CreateSettingRequest;
use Modules\Setting\Requests\DeleteSettingRequest;
use Modules\Setting\Requests\GetSettingListRequest;
use Modules\Setting\Requests\Identifier\GetIdentifierListRequest;
use Modules\Setting\Requests\Identifier\MakeIdentifierDefaultRequest;
use Modules\Setting\Services\IdentifierSettingService;
use Modules\Setting\Services\SettingCRUDService;
use Ramsey\Uuid\Uuid;

class IdentifierSettingController extends Controller
{
    public function __construct(
        private IdentifierSettingService            $identifierSettingService,
        private MakeIdentifierSettingDefaultHandler $makeIdentifierSettingDefaultHandler,
    )
    {
    }

    public function index(GetIdentifierListRequest $request): JsonResponse
    {
        $list = $this->identifierSettingService->list(
            (int)$request->get('page', 1),
            (int)$request->get('per_page', 10)
        );

        return Json::items(IdentifierPresenter::collection($list["data"]),paginationSettings: $list["pagination"]);

    }

    public function makeDefault(MakeIdentifierDefaultRequest $request)
    {
        $id = Uuid::fromString($request->route('id'));
        try {
            $this->makeIdentifierSettingDefaultHandler->handle($id);

        } catch (\Exception $e) {
            return Json::error($e->getMessage(), httpStatus: $e->getCode());
        }

        return Json::success("Identifier default successfully");
    }

}

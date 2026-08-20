<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateConstructionSettingHandler;
use Modules\Project\ProjectType\Presenters\ConstructionSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateConstructionSettingRequest;
use Modules\Project\ProjectType\Services\ConstructionSettingService;

class ConstructionSettingController extends Controller
{
    public function __construct(private readonly ConstructionSettingService $service, private readonly UpdateConstructionSettingHandler $updateHandler) {}
    public function update(UpdateConstructionSettingRequest $request, int $projectTypeId): JsonResponse
    {
        $setting = $this->updateHandler->handle($request->toCommand($projectTypeId));
        return Json::item((new ConstructionSettingPresenter($setting))->getData());
    }
    public function show(int $projectTypeId): JsonResponse
    {
        $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);
        return Json::item((new ConstructionSettingPresenter($setting))->getData());
    }
}
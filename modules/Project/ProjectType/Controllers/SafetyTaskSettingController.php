<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateSafetyTaskSettingHandler;
use Modules\Project\ProjectType\Presenters\SafetyTaskSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateSafetyTaskSettingRequest;
use Modules\Project\ProjectType\Services\SafetyTaskSettingService;

class SafetyTaskSettingController extends Controller
{
    public function __construct(private readonly SafetyTaskSettingService $service, private readonly UpdateSafetyTaskSettingHandler $updateHandler) {}
    public function update(UpdateSafetyTaskSettingRequest $request, int $projectTypeId): JsonResponse
    {
        $setting = $this->updateHandler->handle($request->toCommand($projectTypeId));
        return Json::item((new SafetyTaskSettingPresenter($setting))->getData());
    }
    public function show(int $projectTypeId): JsonResponse
    {
        $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);
        return Json::item((new SafetyTaskSettingPresenter($setting))->getData());
    }
}
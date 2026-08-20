<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateProjectOrderPermitSettingHandler;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateProjectOrderPermitSettingRequest;
use Modules\Project\ProjectType\Services\ProjectOrderPermitSettingService;

class ProjectOrderPermitSettingController extends Controller
{
    public function __construct(private readonly ProjectOrderPermitSettingService $service, private readonly UpdateProjectOrderPermitSettingHandler $updateHandler) {}

    public function update(UpdateProjectOrderPermitSettingRequest $request, int $projectTypeId): JsonResponse
    {
        $setting = $this->updateHandler->handle($request->toCommand($projectTypeId));
        return Json::item((new ProjectOrderPermitSettingPresenter($setting))->getData());
    }

    public function show(int $projectTypeId): JsonResponse
    {
        $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);
        return Json::item((new ProjectOrderPermitSettingPresenter($setting))->getData());
    }
}
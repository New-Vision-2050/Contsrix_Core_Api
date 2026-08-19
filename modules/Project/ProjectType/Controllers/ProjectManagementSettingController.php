<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateProjectManagementSettingHandler;
use Modules\Project\ProjectType\Presenters\ProjectManagementSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateProjectManagementSettingRequest;
use Modules\Project\ProjectType\Services\ProjectManagementSettingService;

class ProjectManagementSettingController extends Controller
{
    public function __construct(private readonly ProjectManagementSettingService $service, private readonly UpdateProjectManagementSettingHandler $updateHandler) {}
    public function update(UpdateProjectManagementSettingRequest $request, int $projectTypeId): JsonResponse
    {
        $setting = $this->updateHandler->handle($request->toCommand($projectTypeId));
        return Json::item((new ProjectManagementSettingPresenter($setting))->getData());
    }
    public function show(int $projectTypeId): JsonResponse
    {
        $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);
        return Json::item((new ProjectManagementSettingPresenter($setting))->getData());
    }
}
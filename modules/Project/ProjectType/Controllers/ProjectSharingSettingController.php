<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateProjectSharingSettingRequest;
use Modules\Project\ProjectType\Services\ProjectSharingSettingService;
use Modules\Project\ProjectType\Handlers\UpdateProjectSharingSettingHandler;
use Modules\Project\ProjectType\Presenters\ProjectSharingSettingPresenter;

class ProjectSharingSettingController extends Controller
{
    public function __construct(
        private readonly ProjectSharingSettingService $service,
        private readonly UpdateProjectSharingSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateProjectSharingSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new ProjectSharingSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project sharing setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new ProjectSharingSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve project sharing setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

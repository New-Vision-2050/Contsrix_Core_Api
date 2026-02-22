<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateProjectDataSettingRequest;
use Modules\Project\ProjectType\Services\ProjectDataSettingService;
use Modules\Project\ProjectType\Handlers\UpdateProjectDataSettingHandler;
use Modules\Project\ProjectType\Presenters\ProjectDataSettingPresenter;

class ProjectDataSettingController extends Controller
{
    public function __construct(
        private readonly ProjectDataSettingService $service,
        private readonly UpdateProjectDataSettingHandler $updateHandler
    ) {
    }

    /**
     * Update project data setting for a project type
     * All fields are optional - can update one or all fields
     */
    public function update(UpdateProjectDataSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new ProjectDataSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project data setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project data setting for a project type
     */
    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item((new ProjectDataSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project data setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

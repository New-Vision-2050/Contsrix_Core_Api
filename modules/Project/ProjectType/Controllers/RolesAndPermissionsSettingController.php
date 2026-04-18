<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateRolesAndPermissionsSettingRequest;
use Modules\Project\ProjectType\Services\RolesAndPermissionsSettingService;
use Modules\Project\ProjectType\Handlers\UpdateRolesAndPermissionsSettingHandler;
use Modules\Project\ProjectType\Presenters\RolesAndPermissionsSettingPresenter;

class RolesAndPermissionsSettingController extends Controller
{
    public function __construct(
        private readonly RolesAndPermissionsSettingService $service,
        private readonly UpdateRolesAndPermissionsSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateRolesAndPermissionsSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new RolesAndPermissionsSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update roles and permissions setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new RolesAndPermissionsSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles and permissions setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

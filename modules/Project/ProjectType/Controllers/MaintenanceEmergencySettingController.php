<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateMaintenanceEmergencySettingRequest;
use Modules\Project\ProjectType\Services\MaintenanceEmergencySettingService;
use Modules\Project\ProjectType\Handlers\UpdateMaintenanceEmergencySettingHandler;
use Modules\Project\ProjectType\Presenters\MaintenanceEmergencySettingPresenter;

class MaintenanceEmergencySettingController extends Controller
{
    public function __construct(
        private readonly MaintenanceEmergencySettingService $service,
        private readonly UpdateMaintenanceEmergencySettingHandler $updateHandler
    ) {
    }

    public function update(UpdateMaintenanceEmergencySettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new MaintenanceEmergencySettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance and emergency setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new MaintenanceEmergencySettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve maintenance and emergency setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

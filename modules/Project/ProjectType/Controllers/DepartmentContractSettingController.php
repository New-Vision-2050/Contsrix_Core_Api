<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateDepartmentContractSettingRequest;
use Modules\Project\ProjectType\Services\DepartmentContractSettingService;
use Modules\Project\ProjectType\Handlers\UpdateDepartmentContractSettingHandler;

class DepartmentContractSettingController extends Controller
{
    public function __construct(
        private readonly DepartmentContractSettingService $service,
        private readonly UpdateDepartmentContractSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateDepartmentContractSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return response()->json([
                'success' => true,
                'message' => 'Department contract setting updated successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update department contract setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item($setting);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Department contract setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

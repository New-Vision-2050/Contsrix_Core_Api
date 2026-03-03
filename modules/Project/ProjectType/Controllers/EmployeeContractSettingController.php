<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateEmployeeContractSettingRequest;
use Modules\Project\ProjectType\Services\EmployeeContractSettingService;
use Modules\Project\ProjectType\Handlers\UpdateEmployeeContractSettingHandler;
use Modules\Project\ProjectType\Presenters\EmployeeContractSettingPresenter;

class EmployeeContractSettingController extends Controller
{
    public function __construct(
        private readonly EmployeeContractSettingService $service,
        private readonly UpdateEmployeeContractSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateEmployeeContractSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new EmployeeContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update employee contract setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item((new EmployeeContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employee contract setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

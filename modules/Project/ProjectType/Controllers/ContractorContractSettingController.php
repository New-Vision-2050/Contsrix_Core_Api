<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateContractorContractSettingRequest;
use Modules\Project\ProjectType\Services\ContractorContractSettingService;
use Modules\Project\ProjectType\Handlers\UpdateContractorContractSettingHandler;

class ContractorContractSettingController extends Controller
{
    public function __construct(
        private readonly ContractorContractSettingService $service,
        private readonly UpdateContractorContractSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateContractorContractSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return response()->json([
                'success' => true,
                'message' => 'Contractor contract setting updated successfully',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contractor contract setting',
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
                'message' => 'Contractor contract setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

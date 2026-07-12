<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateContractorSettingHandler;
use Modules\Project\ProjectType\Presenters\ContractorSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateContractorSettingRequest;
use Modules\Project\ProjectType\Services\ContractorSettingService;

class ContractorSettingController extends Controller
{
    public function __construct(
        private readonly ContractorSettingService $service,
        private readonly UpdateContractorSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateContractorSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new ContractorSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update contractor setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new ContractorSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve contractor setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

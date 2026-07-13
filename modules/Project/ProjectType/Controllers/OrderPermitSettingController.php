<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Handlers\UpdateOrderPermitSettingHandler;
use Modules\Project\ProjectType\Presenters\OrderPermitSettingPresenter;
use Modules\Project\ProjectType\Requests\UpdateOrderPermitSettingRequest;
use Modules\Project\ProjectType\Services\OrderPermitSettingService;

class OrderPermitSettingController extends Controller
{
    public function __construct(
        private readonly OrderPermitSettingService $service,
        private readonly UpdateOrderPermitSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateOrderPermitSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new OrderPermitSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order permit setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new OrderPermitSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order permit setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

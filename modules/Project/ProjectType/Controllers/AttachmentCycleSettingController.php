<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateAttachmentCycleSettingRequest;
use Modules\Project\ProjectType\Services\AttachmentCycleSettingService;
use Modules\Project\ProjectType\Handlers\UpdateAttachmentCycleSettingHandler;
use Modules\Project\ProjectType\Presenters\AttachmentCycleSettingPresenter;

class AttachmentCycleSettingController extends Controller
{
    public function __construct(
        private readonly AttachmentCycleSettingService $service,
        private readonly UpdateAttachmentCycleSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateAttachmentCycleSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new AttachmentCycleSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attachment cycle setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getOrCreateByProjectTypeId($projectTypeId);

            return Json::item((new AttachmentCycleSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve attachment cycle setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

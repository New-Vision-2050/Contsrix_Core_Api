<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateAttachmentContractSettingRequest;
use Modules\Project\ProjectType\Services\AttachmentContractSettingService;
use Modules\Project\ProjectType\Handlers\UpdateAttachmentContractSettingHandler;
use Modules\Project\ProjectType\Presenters\AttachmentContractSettingPresenter;

class AttachmentContractSettingController extends Controller
{
    public function __construct(
        private readonly AttachmentContractSettingService $service,
        private readonly UpdateAttachmentContractSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateAttachmentContractSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new AttachmentContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attachment contract setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item((new AttachmentContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment contract setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateAttachmentTermsContractSettingRequest;
use Modules\Project\ProjectType\Services\AttachmentTermsContractSettingService;
use Modules\Project\ProjectType\Handlers\UpdateAttachmentTermsContractSettingHandler;
use Modules\Project\ProjectType\Presenters\AttachmentTermsContractSettingPresenter;

class AttachmentTermsContractSettingController extends Controller
{
    public function __construct(
        private readonly AttachmentTermsContractSettingService $service,
        private readonly UpdateAttachmentTermsContractSettingHandler $updateHandler
    ) {
    }

    public function update(UpdateAttachmentTermsContractSettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new AttachmentTermsContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update attachment terms contract setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item((new AttachmentTermsContractSettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attachment terms contract setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

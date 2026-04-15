<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectType\Requests\UpdateArchiveLibrarySettingRequest;
use Modules\Project\ProjectType\Services\ArchiveLibrarySettingService;
use Modules\Project\ProjectType\Handlers\UpdateArchiveLibrarySettingHandler;
use Modules\Project\ProjectType\Presenters\ArchiveLibrarySettingPresenter;

class ArchiveLibrarySettingController extends Controller
{
    public function __construct(
        private readonly ArchiveLibrarySettingService $service,
        private readonly UpdateArchiveLibrarySettingHandler $updateHandler
    ) {
    }

    public function update(UpdateArchiveLibrarySettingRequest $request, int $projectTypeId): JsonResponse
    {
        try {
            $command = $request->toCommand($projectTypeId);
            $setting = $this->updateHandler->handle($command);

            return Json::item((new ArchiveLibrarySettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update archive library setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(int $projectTypeId): JsonResponse
    {
        try {
            $setting = $this->service->getByProjectTypeId($projectTypeId);

            return Json::item((new ArchiveLibrarySettingPresenter($setting))->getData());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Archive library setting not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}

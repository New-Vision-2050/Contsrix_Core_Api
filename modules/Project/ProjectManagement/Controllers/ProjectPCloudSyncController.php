<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Facade\Json;
use Illuminate\Http\JsonResponse;
use Modules\Project\ProjectManagement\Actions\SyncProjectPCloudAction;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use Modules\Project\ProjectManagement\Exceptions\ProjectPCloudNotFoundException;
use Modules\Project\ProjectManagement\Requests\SyncProjectPCloudRequest;
use RuntimeException;

class ProjectPCloudSyncController extends Controller
{
    public function __invoke(
        SyncProjectPCloudRequest $request,
        SyncProjectPCloudAction $syncProjectPCloud,
    ): JsonResponse
    {
        try {
            $result = $syncProjectPCloud->execute(
                $request->projectId(),
                (string) tenant('id'),
            );

            return Json::item(
                $result['payload'],
                message: $result['queued'] ? 'PCloud export queued' : 'PCloud export completed',
                httpStatus: $result['queued'] ? 202 : 200,
            );
        } catch (ProjectPCloudNotFoundException) {
            return Json::error('Project not found', httpStatus: 404);
        } catch (PCloudConfigurationException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 422);
        } catch (RuntimeException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 502);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Facade\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Exceptions\PCloudApiException;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use Modules\Project\ProjectManagement\Jobs\ExportProjectPCloudArchiveJob;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectPCloudExportService;

class ProjectPCloudSyncController extends Controller
{
    public function __invoke(string $project, ProjectPCloudExportService $service): JsonResponse
    {
        $companyId = (string) tenant('id');
        $projectModel = ProjectManagement::query()
            ->where('id', $project)
            ->where('company_id', $companyId)
            ->first();

        if (! $projectModel) {
            return Json::error('Project not found', httpStatus: 404);
        }

        try {
            $service->ensureConfigured();
            $runId = (string) Str::uuid();

            if ($service->dispatchMode() === 'queue') {
                ExportProjectPCloudArchiveJob::dispatch(
                    (string) $projectModel->id,
                    $companyId,
                    $runId,
                );

                return Json::item(
                    [
                        'run_id' => $runId,
                        'project_id' => (string) $projectModel->id,
                        'mode' => 'queue',
                        'path' => $service->targetPath($projectModel),
                    ],
                    message: 'PCloud export queued',
                    httpStatus: 202,
                );
            }

            return Json::item(
                $service->export($projectModel, $runId),
                message: 'PCloud export completed',
            );
        } catch (PCloudConfigurationException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 422);
        } catch (PCloudApiException $exception) {
            return Json::error($exception->getMessage(), httpStatus: 502);
        }
    }
}

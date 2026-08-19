<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Actions;

use Illuminate\Support\Str;
use Modules\Project\ProjectManagement\Exceptions\ProjectPCloudNotFoundException;
use Modules\Project\ProjectManagement\Jobs\ExportProjectPCloudArchiveJob;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectPCloudExportService;

final class SyncProjectPCloudAction
{
    public function __construct(
        private readonly ProjectPCloudExportService $service,
    ) {}

    /**
     * @return array{queued: bool, payload: array<string, int|string>}
     */
    public function execute(string $projectId, string $companyId): array
    {
        $project = ProjectManagement::query()
            ->where('id', $projectId)
            ->where('company_id', $companyId)
            ->first();

        if (! $project) {
            throw new ProjectPCloudNotFoundException;
        }

        $this->service->ensureConfigured();
        $runId = (string) Str::uuid();

        if ($this->service->dispatchMode() === 'queue') {
            ExportProjectPCloudArchiveJob::dispatch(
                (string) $project->id,
                $companyId,
                $runId,
            );

            return [
                'queued' => true,
                'payload' => [
                    'run_id' => $runId,
                    'project_id' => (string) $project->id,
                    'mode' => 'queue',
                    'path' => $this->service->targetPath($project),
                ],
            ];
        }

        return [
            'queued' => false,
            'payload' => $this->service->export($project, $runId),
        ];
    }
}

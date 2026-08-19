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
     * @return array{queued: bool, payload: array<string, mixed>}
     */
    public function execute(?string $projectId, string $companyId): array
    {
        if ($projectId === null) {
            return $this->syncAllProjects($companyId);
        }

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

    /**
     * @return array{queued: bool, payload: array<string, mixed>}
     */
    private function syncAllProjects(string $companyId): array
    {
        $projects = ProjectManagement::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        $this->service->ensureConfigured();
        $runId = (string) Str::uuid();

        if ($projects->isEmpty()) {
            return [
                'queued' => false,
                'payload' => [
                    'run_id' => $runId,
                    'projects_count' => 0,
                    'projects' => [],
                ],
            ];
        }

        if ($this->service->dispatchMode() === 'queue') {
            $projectsPayload = $projects->map(function (ProjectManagement $project) use ($companyId, $runId): array {
                ExportProjectPCloudArchiveJob::dispatch(
                    (string) $project->id,
                    $companyId,
                    $runId,
                );

                return [
                    'project_id' => (string) $project->id,
                    'path' => $this->service->targetPath($project),
                ];
            })->values()->all();

            return [
                'queued' => true,
                'payload' => [
                    'run_id' => $runId,
                    'projects_count' => count($projectsPayload),
                    'projects' => $projectsPayload,
                ],
            ];
        }

        return [
            'queued' => false,
            'payload' => [
                'run_id' => $runId,
                'projects_count' => $projects->count(),
                'projects' => $projects
                    ->map(fn (ProjectManagement $project): array => $this->service->export($project, $runId))
                    ->values()
                    ->all(),
            ],
        ];
    }
}

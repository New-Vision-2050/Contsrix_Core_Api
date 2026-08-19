<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
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
            $this->initializeCompletionCounter($runId);

            ExportProjectPCloudArchiveJob::dispatch(
                projectId: (string) $project->id,
                companyId: $companyId,
                runId: $runId,
                projectsCount: 1,
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

        $payload = $this->service->export($project, $runId);
        $this->sendCompletionEmail($runId, 1);

        return [
            'queued' => false,
            'payload' => $payload,
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
            $this->sendCompletionEmail($runId, 0);

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
            $projectsCount = $projects->count();
            $this->initializeCompletionCounter($runId);

            $projectsPayload = $projects->map(function (ProjectManagement $project) use ($companyId, $runId, $projectsCount): array {
                ExportProjectPCloudArchiveJob::dispatch(
                    projectId: (string) $project->id,
                    companyId: $companyId,
                    runId: $runId,
                    projectsCount: $projectsCount,
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

        $projectsPayload = $projects
            ->map(fn (ProjectManagement $project): array => $this->service->export($project, $runId))
            ->values()
            ->all();

        $this->sendCompletionEmail($runId, $projects->count());

        return [
            'queued' => false,
            'payload' => [
                'run_id' => $runId,
                'projects_count' => $projects->count(),
                'projects' => $projectsPayload,
            ],
        ];
    }

    private function initializeCompletionCounter(string $runId): void
    {
        Cache::put("pcloud-sync:{$runId}:completed", 0, now()->addDay());
    }

    private function sendCompletionEmail(string $runId, int $projectsCount): void
    {
        Mail::raw(
            "PCloud sync finished. Run: {$runId}. Projects: {$projectsCount}.",
            static fn ($message) => $message
                ->to('dev.desoky@gmail.com')
                ->subject('PCloud sync finished'),
        );
    }
}

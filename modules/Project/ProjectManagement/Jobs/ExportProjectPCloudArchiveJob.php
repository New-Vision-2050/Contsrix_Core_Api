<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectPCloudExportService;
use Throwable;

class ExportProjectPCloudArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $projectId,
        public readonly string $companyId,
        public readonly string $runId,
        public readonly int $projectsCount = 1,
    ) {}

    public function handle(ProjectPCloudExportService $service): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $project = ProjectManagement::query()
                ->where('id', $this->projectId)
                ->where('company_id', $this->companyId)
                ->first();

            if (! $project) {
                Log::warning('PCloud project export skipped because project was not found.', [
                    'run_id' => $this->runId,
                    'project_id' => $this->projectId,
                    'company_id' => $this->companyId,
                ]);

            } else {
                $result = $service->export($project, $this->runId);

                Log::info('PCloud project export completed.', [
                    'run_id' => $this->runId,
                    'project_id' => $this->projectId,
                    'company_id' => $this->companyId,
                    'folders_created_or_found' => $result['folders_created_or_found'],
                    'files_uploaded' => $result['files_uploaded'],
                    'files_skipped' => $result['files_skipped'],
                    'files_failed' => $result['files_failed'],
                    'path' => $result['path'],
                ]);
            }
        } finally {
            tenancy()->end();
        }

        $this->sendCompletionEmailWhenLastJobFinishes();
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('PCloud project export failed permanently.', [
            'run_id' => $this->runId,
            'project_id' => $this->projectId,
            'company_id' => $this->companyId,
            'error' => $exception?->getMessage(),
        ]);

        $this->sendCompletionEmailWhenLastJobFinishes();
    }

    private function sendCompletionEmailWhenLastJobFinishes(): void
    {
        $key = "pcloud-sync:{$this->runId}:completed";
        $completed = (int) Cache::increment($key);

        if ($completed !== $this->projectsCount) {
            return;
        }

        Cache::forget($key);

        try {
            Mail::raw(
                "PCloud sync finished. Run: {$this->runId}. Projects: {$this->projectsCount}.",
                static fn ($message) => $message
                    ->to('dev.desoky@gmail.com')
                    ->subject('PCloud sync finished'),
            );
        } catch (Throwable $exception) {
            Log::error('PCloud sync completion email failed.', [
                'run_id' => $this->runId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Services\ProjectPCloudExportService;

class ExportProjectPCloudArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $projectId,
        public readonly string $companyId,
        public readonly string $runId,
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

                return;
            }

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
        } finally {
            tenancy()->end();
        }
    }
}

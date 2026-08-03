<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveProfileSyncService;
use Throwable;

class SyncEmployeeArchiveProfileJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 1200;

    public int $backoff = 60;

    /**
     * @param array<int, string>|null $employeeGlobalIds
     */
    public function __construct(
        public readonly string $companyId,
        public readonly bool $dryRun = false,
        public readonly ?array $employeeGlobalIds = null,
    ) {
    }

    public function handle(EmployeeArchiveProfileSyncService $syncService): void
    {
        $summary = $syncService->sync(
            companyId: $this->companyId,
            dryRun: $this->dryRun,
            employeeGlobalIds: $this->employeeGlobalIds,
        );

        $context = [
            'company_id' => $this->companyId,
            'employee_global_ids' => $this->employeeGlobalIds,
            'dry_run' => $this->dryRun,
            'summary' => $summary,
        ];

        if (! empty($summary['errors'])) {
            Log::warning('Employee archive profile sync completed with errors.', $context);

            return;
        }

        Log::info('Employee archive profile sync completed.', $context);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Employee archive profile sync job failed permanently.', [
            'company_id' => $this->companyId,
            'employee_global_ids' => $this->employeeGlobalIds,
            'dry_run' => $this->dryRun,
            'error' => $exception?->getMessage(),
        ]);
    }
}

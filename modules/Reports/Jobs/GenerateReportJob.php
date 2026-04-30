<?php

declare(strict_types=1);

namespace Modules\Reports\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Reports\Models\Report;
use Modules\Reports\Repositories\ReportRepository;
use Modules\Reports\Services\ReportGenerationService;
use Stancl\Tenancy\Tenancy;

/**
 * Queued worker that generates the file artifact for a saved Report row.
 *
 * The job is tenant-aware: the company UUID captured at dispatch time is used
 * to re-initialise the tenancy context inside the worker process so any query
 * scoped through `tenant('id')` resolves the correct company.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;

    public function __construct(
        public string $reportId,
        public string $companyId,
    ) {
    }

    public function handle(
        Tenancy                 $tenancy,
        ReportRepository        $repository,
        ReportGenerationService $generation,
    ): void {
        // Re-establish tenancy inside the worker so tenant('id') resolves.
        if (!$tenancy->initialized || (string) $tenancy->tenant?->id !== $this->companyId) {
            $tenant = config('tenancy.tenant_model')::find($this->companyId);
            if ($tenant) {
                $tenancy->initialize($tenant);
            }
        }

        /** @var Report|null $report */
        $report = $repository->find($this->reportId);
        if ($report === null) {
            return;
        }

        $generation->generate($report);
    }
}

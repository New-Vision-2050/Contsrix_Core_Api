<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ArchiveLibrary\File\Services\EmployeeArchiveProfileSyncService;

class SyncEmployeeArchiveCommand extends Command
{
    protected $signature = 'employee-archive:sync
                            {--company= : Only sync a specific company/tenant ID}
                            {--employee=* : Only sync one or more employee global IDs}
                            {--dry-run : Preview changes without writing folders, files, or media}';

    protected $description = 'Backfill and sync Employee Profile attachments into the Employee Archive Library';

    public function handle(EmployeeArchiveProfileSyncService $syncService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $summary = $syncService->sync(
            companyId: $this->option('company') ?: null,
            employeeGlobalIds: $this->employeeGlobalIds(),
            dryRun: $dryRun,
        );

        $prefix = $dryRun ? '[DRY RUN] ' : '';

        $this->info($prefix.'Employee archive sync complete.');
        $this->line("Companies: {$summary['companies']}");
        $this->line("Employees: {$summary['employees']}");
        $this->line("Media checked: {$summary['media_checked']}");
        $this->line("Created: {$summary['created']}");
        $this->line("Updated: {$summary['updated']}");
        $this->line("Attached media: {$summary['attached']}");
        $this->line("Unchanged: {$summary['unchanged']}");
        $this->line("Skipped: {$summary['skipped']}");

        foreach ($summary['errors'] as $error) {
            $this->error(sprintf(
                'Company %s employee %s: %s',
                $error['company_id'] ?? '-',
                $error['employee_global_id'] ?? '-',
                $error['message']
            ));
        }

        return empty($summary['errors']) ? self::SUCCESS : self::FAILURE;
    }

    private function employeeGlobalIds(): ?array
    {
        $employeeIds = array_values(array_filter(
            array_map('trim', (array) $this->option('employee'))
        ));

        return $employeeIds === [] ? null : $employeeIds;
    }
}

<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SyncSafetyRecordsFromNotificationsCommand extends Command
{
    protected $signature = 'safety:sync-notification-data
                            {--company= : Only sync a specific company/tenant ID}
                            {--dry-run : Preview the changes without saving them}';

    protected $description = 'Backfill null contractor_id, order_type, consultant, date and time on old safety records that were auto-created from project notifications';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companyId = $this->option('company');

        $companies = $companyId
            ? Company::withoutGlobalScopes()->where('id', $companyId)->get()
            : Company::withoutGlobalScopes()->get();

        if ($companies->isEmpty()) {
            $this->warn('No matching companies found.');

            return self::SUCCESS;
        }

        $previousTenant = tenancy()->initialized ? tenant() : null;

        $totalUpdated = 0;
        $totalChecked = 0;

        foreach ($companies as $company) {
            tenancy()->end();
            tenancy()->initialize($company);

            [$checked, $updated] = $this->syncForCurrentTenant($dryRun);

            $totalChecked += $checked;
            $totalUpdated += $updated;

            if ($checked > 0) {
                $this->line("Company <fg=cyan>{$company->id}</>: checked {$checked}, updated {$updated}");
            }
        }

        tenancy()->end();
        if ($previousTenant) {
            tenancy()->initialize($previousTenant);
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '')."Done. Checked {$totalChecked} safety record(s), updated {$totalUpdated}.");

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int} [checked, updated]
     */
    private function syncForCurrentTenant(bool $dryRun): array
    {
        $records = SafetyRecord::query()
            ->where('morphable_type', 'project_notification')
            ->where(function (Builder $query) {
                $query->whereNull('contractor_id')
                    ->orWhereNull('order_type')
                    ->orWhereNull('consultant')
                    ->orWhereNull('date')
                    ->orWhereNull('time');
            })
            ->with('morphable')
            ->get();

        $updated = 0;

        foreach ($records as $record) {
            $notification = $record->morphable;

            if (! $notification instanceof ProjectNotification) {
                continue;
            }

            $record->contractor_id ??= $notification->contractor_id;
            $record->order_type ??= $notification->work_type;
            $record->consultant ??= tenant('name');
            $record->date ??= $notification->task_date?->toDateString();
            $record->time ??= $notification->task_time?->format('H:i');

            if ($record->isDirty()) {
                $updated++;

                if (! $dryRun) {
                    $record->save();
                }
            }
        }

        return [$records->count(), $updated];
    }
}

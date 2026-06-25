<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Company\CompanyCore\Models\Company;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Stancl\Tenancy\Tenancy;

class AutoRejectStaleTasksCommand extends Command
{
    protected $signature = 'employee-task:auto-reject-stale
                            {--dry-run : Show which tasks would be rejected without writing to DB}
                            {--date= : Reference date in Y-m-d format (defaults to today in app timezone)}';

    protected $description = 'Auto-reject employee tasks whose task_date has passed. '
        . 'Handles two cases: (1) tasks still pending in approval workflow, '
        . '(2) approved tasks that were never started. '
        . 'Runs every 5 minutes. In-progress/paused tasks are NOT touched '
        . '(handled by AutoCloseTaskAtDurationExpiryJob).';

    public function handle(Tenancy $tenancy): int
    {
        $isDryRun = $this->option('dry-run');
        $timezone = config('app.timezone', 'Asia/Riyadh');

        $today = $this->option('date')
            ? Carbon::parse($this->option('date'), $timezone)->startOfDay()
            : Carbon::now($timezone)->startOfDay();

        if ($isDryRun) {
            $this->info('[DRY RUN] No DB writes will occur.');
        }

        $this->info("Reference date (today): {$today->toDateString()} TZ={$timezone}");

        $companies = Company::get();

        $rejected = 0;
        $skipped  = 0;

        foreach ($companies as $company) {
            $tenancy->initialize($company);

            try {
                $count = $this->processCompany($today, $isDryRun, $timezone, $company->id);
                $rejected += $count;
            } catch (\Throwable $e) {
                $this->error("  Error processing company {$company->id}: {$e->getMessage()}");
                Log::error('AutoRejectStaleTasks error', [
                    'company_id' => $company->id,
                    'error'      => $e->getMessage(),
                ]);
            } finally {
                $tenancy->end();
            }
        }

        $this->info("Done — rejected: {$rejected}, skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function processCompany(Carbon $today, bool $isDryRun, string $timezone, string $companyId): int
    {
        $staleTasks = EmployeeTaskRequest::query()
            ->whereIn('status', [
                EmployeeTaskStatus::Pending->value,
                EmployeeTaskStatus::Approved->value,
            ])
            ->whereDate('task_date', '<', $today->toDateString())
            ->get();

        if ($staleTasks->isEmpty()) {
            return 0;
        }

        $this->line("  Company {$companyId}: found {$staleTasks->count()} stale tasks.");

        $rejected = 0;

        foreach ($staleTasks as $task) {
            $reason = $task->status === EmployeeTaskStatus::Pending->value
                ? 'Task auto-rejected: task date passed while still pending approval.'
                : 'Task auto-rejected: task date passed without the employee starting the task.';

            if ($isDryRun) {
                $this->line("    WOULD REJECT task {$task->id} (status: {$task->status}, task_date: {$task->task_date}) — {$reason}");
                $rejected++;
                continue;
            }

            DB::transaction(function () use ($task, $reason, $today, $timezone, &$rejected) {
                $fresh = EmployeeTaskRequest::query()
                    ->lockForUpdate()
                    ->find($task->id);

                if (!$fresh) {
                    return;
                }

                if (!in_array($fresh->status, [EmployeeTaskStatus::Pending->value, EmployeeTaskStatus::Approved->value], true)) {
                    return;
                }

                $now = CarbonImmutable::now($timezone);

                $fresh->update([
                    'status'            => EmployeeTaskStatus::Rejected->value,
                    'rejected_at'       => $now->toDateTimeString(),
                    'rejection_reason'  => $reason,
                ]);

                $rejected++;

                Log::info('Auto-rejected stale employee task', [
                    'task_id'    => $fresh->id,
                    'company_id' => $fresh->company_id,
                    'user_id'    => $fresh->user_id,
                    'old_status' => $task->status,
                    'task_date'  => $fresh->task_date?->toDateString(),
                    'reason'     => $reason,
                ]);

                $this->line("    rejected task {$fresh->id} (was: {$task->status}) — {$reason}");
            });
        }

        return $rejected;
    }
}

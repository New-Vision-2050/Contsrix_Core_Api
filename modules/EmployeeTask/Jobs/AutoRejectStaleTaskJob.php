<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Jobs;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;

/**
 * Queued job that fires at midnight (end of task_date) in the employee's branch timezone.
 *
 * If the task is still pending, approved (never started), or paused at that point,
 * it is auto-rejected so it won't appear in reports.
 *
 * If the task is already in_progress (handled by AutoCloseTaskAtDurationExpiryJob),
 * completed, rejected, or cancelled, the job is a no-op.
 */
class AutoRejectStaleTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $taskId,
        public readonly string $companyId,
    ) {}

    public function handle(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        tenancy()->initialize($this->companyId);

        try {
            $task = EmployeeTaskRequest::find($this->taskId);

            if (!$task) {
                return;
            }

            if (!in_array($task->status, [
                EmployeeTaskStatus::Pending->value,
                EmployeeTaskStatus::Approved->value,
                EmployeeTaskStatus::Paused->value,
            ], true)) {
                Log::info('AutoRejectStaleTaskJob: task already active/closed, skipping', [
                    'task_id' => $task->id,
                    'status'  => $task->status,
                ]);
                return;
            }

            DB::transaction(function () use ($task): void {
                $fresh = EmployeeTaskRequest::query()
                    ->lockForUpdate()
                    ->find($task->id);

                if (!$fresh) {
                    return;
                }

                if (!in_array($fresh->status, [
                    EmployeeTaskStatus::Pending->value,
                    EmployeeTaskStatus::Approved->value,
                    EmployeeTaskStatus::Paused->value,
                ], true)) {
                    return;
                }

                $reason = match ($fresh->status) {
                    EmployeeTaskStatus::Pending->value => 'Task auto-rejected: task date passed while still pending approval.',
                    EmployeeTaskStatus::Paused->value  => 'Task auto-rejected: task was paused and never resumed before task date passed.',
                    default                            => 'Task auto-rejected: task date passed without the employee starting the task.',
                };

                $timezone = $fresh->timezone ?: config('app.timezone', 'Asia/Riyadh');
                $now = CarbonImmutable::now($timezone);

                $fresh->update([
                    'status'           => EmployeeTaskStatus::Rejected->value,
                    'rejected_at'      => $now->toDateTimeString(),
                    'rejection_reason' => $reason,
                ]);

                Log::info('AutoRejectStaleTaskJob: task auto-rejected', [
                    'task_id'    => $fresh->id,
                    'company_id' => $fresh->company_id,
                    'user_id'    => $fresh->user_id,
                    'old_status' => $task->status,
                    'reason'     => $reason,
                ]);
            });
        } finally {
            tenancy()->end();
        }
    }
}

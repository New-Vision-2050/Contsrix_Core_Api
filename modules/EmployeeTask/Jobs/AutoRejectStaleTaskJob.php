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
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;

/**
 * Queued job that fires at midnight (end of task_date) in the employee's branch timezone.
 *
 * If the task is still pending, approved (never started), paused, or in_progress
 * with a pending end request at that point, it is auto-rejected so it won't
 * appear in reports. Pending sub-requests (end, start, approval, extension)
 * are also rejected so they disappear from the admin inbox.
 *
 * If the task is already completed, rejected, or cancelled, the job is a no-op.
 */
class AutoRejectStaleTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $taskId,
        public readonly string $companyId,
    ) {}

    public function handle(EmployeeTaskRepository $repository): void
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

            // Project notification tasks are never auto-rejected. They follow
            // a separate lifecycle managed by the project notification module.
            if ($task->is_project_notification) {
                return;
            }

            if (!in_array($task->status, [
                EmployeeTaskStatus::Pending->value,
                EmployeeTaskStatus::Approved->value,
                EmployeeTaskStatus::Paused->value,
                EmployeeTaskStatus::InProgress->value,
            ], true)) {
                Log::info('AutoRejectStaleTaskJob: task already closed, skipping', [
                    'task_id' => $task->id,
                    'status'  => $task->status,
                ]);
                return;
            }

            DB::transaction(function () use ($task, $repository): void {
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
                    EmployeeTaskStatus::InProgress->value,
                ], true)) {
                    return;
                }

                $reason = match ($fresh->status) {
                    EmployeeTaskStatus::Pending->value     => 'Task auto-rejected: task date passed while still pending approval.',
                    EmployeeTaskStatus::Paused->value      => 'Task auto-rejected: task was paused and never resumed before task date passed.',
                    EmployeeTaskStatus::InProgress->value  => 'Task auto-rejected: duration expired without manual end.',
                    default                                => 'Task auto-rejected: task date passed without the employee starting the task.',
                };

                $timezone = $fresh->timezone ?: config('app.timezone', 'Asia/Riyadh');
                $now = CarbonImmutable::now($timezone);

                if ($fresh->status === EmployeeTaskStatus::InProgress->value) {
                    $repository->closeActiveSessionForTask($fresh->id, $now->toDateTimeString());
                }

                $fresh->update([
                    'status'           => EmployeeTaskStatus::Rejected->value,
                    'rejected_at'      => $now->toDateTimeString(),
                    'rejection_reason' => $reason,
                ]);

                $repository->cancelPendingSubRequests($fresh->id, $reason);

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

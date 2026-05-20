<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\DTO\ApproveExtensionRequestDTO;
use Modules\EmployeeTask\DTO\RejectExtensionRequestDTO;
use Modules\EmployeeTask\Enums\EmployeeTaskStatus;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;

final class EmployeeTaskExtensionResolveService
{
    public function __construct(
        private readonly EmployeeTaskRepository $taskRepository,
    ) {}

    /**
     * Approve an extension request.
     *
     * Process:
     * 1. Validate extension exists and is pending
     * 2. Validate associated task exists
     * 3. Store original task duration if not already stored
     * 4. Update task duration with additional hours
     * 5. Update extension status to approved
     * 6. Dispatch auto-close job with new deadline
     * 7. Update task's last_extension_status badge
     *
     * All changes wrapped in a database transaction.
     *
     * @throws EmployeeTaskException
     */
    public function approve(ApproveExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
    {
        $extension = $this->findExtensionOrFail($dto->extensionId);
        $task = $this->findTaskOrFail($extension->employee_task_request_id);

        $this->validateExtensionCanBeResolved($extension);

        return DB::transaction(function () use ($extension, $task, $dto): EmployeeTaskExtensionRequest {
            // Store original duration if this is the first extension
            if ($task->original_duration_hours === null) {
                $task->update([
                    'original_duration_hours' => $task->duration_hours,
                ]);
            }

            // Calculate new duration
            $newDuration = (float) $task->duration_hours + (float) $extension->additional_hours;

            // Update task with new duration
            $task->update([
                'duration_hours'         => $newDuration,
                'last_extension_status'  => 'extension_approved',
            ]);

            // Update extension as approved
            $extension->update([
                'status'      => 'approved',
                'reviewed_by' => $dto->adminId,
                'reviewed_at' => now(),
                'review_notes' => $dto->approvalNotes,
            ]);

            // Dispatch auto-close job with updated deadline if task has started
            if ($task->time_from) {
                $this->dispatchAutoCloseJob($task, $newDuration);
            }

            return $extension->fresh();
        });
    }

    /**
     * Reject an extension request.
     *
     * Process:
     * 1. Validate extension exists and is pending
     * 2. Validate associated task exists
     * 3. Update extension status to rejected with reason
     * 4. Update task's last_extension_status badge
     *
     * All changes wrapped in a database transaction.
     *
     * @throws EmployeeTaskException
     */
    public function reject(RejectExtensionRequestDTO $dto): EmployeeTaskExtensionRequest
    {
        $extension = $this->findExtensionOrFail($dto->extensionId);
        $task = $this->findTaskOrFail($extension->employee_task_request_id);

        $this->validateExtensionCanBeResolved($extension);

        return DB::transaction(function () use ($extension, $task, $dto): EmployeeTaskExtensionRequest {
            // Update extension as rejected
            $extension->update([
                'status'       => 'rejected',
                'reviewed_by'  => $dto->adminId,
                'reviewed_at'  => now(),
                'review_notes' => $dto->rejectionReason,
            ]);

            // Update task's extension badge
            $task->update([
                'last_extension_status' => 'extension_rejected',
            ]);

            return $extension->fresh();
        });
    }

    /**
     * Retrieve a single extension request with full eager loading.
     *
     * @throws EmployeeTaskException
     */
    public function get(string $extensionId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()
            ->with([
                'task',
                'requestedByUser',
                'reviewedByUser',
            ])
            ->find($extensionId);

        if (!$extension) {
            throw EmployeeTaskException::extensionNotFound();
        }

        return $extension;
    }

    /**
     * List all pending extension requests (admin inbox).
     *
     * Ordered by creation date (newest first) for easy scanning.
     */
    public function listPending(int $perPage = 15)
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('status', 'pending')
            ->with([
                'task',
                'requestedByUser',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * List all extension requests for a specific task.
     *
     * Useful for viewing history of extensions on a single task.
     */
    public function listForTask(string $taskId)
    {
        return EmployeeTaskExtensionRequest::query()
            ->where('employee_task_request_id', $taskId)
            ->with([
                'requestedByUser',
                'reviewedByUser',
            ])
            ->orderByDesc('created_at')
            ->get();
    }


    private function findExtensionOrFail(string $extensionId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()->find($extensionId);

        if (!$extension) {
            throw EmployeeTaskException::extensionNotFound();
        }

        return $extension;
    }

    /**
     * Find a task or throw exception.
     */
    private function findTaskOrFail(string $taskId)
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw EmployeeTaskException::taskForExtensionNotFound($taskId);
        }

        return $task;
    }

    /**
     * Validate that an extension is in a resolvable state (pending).
     *
     * @throws EmployeeTaskException
     */
    private function validateExtensionCanBeResolved(EmployeeTaskExtensionRequest $extension): void
    {
        if ($extension->status !== 'pending') {
            throw EmployeeTaskException::extensionAlreadyResolved();
        }
    }

    /**
     * Dispatch the auto-close job with the updated deadline.
     *
     * Calculates the new close time based on task start time + new total duration.
     * Uses the same timezone logic as the original extension service.
     *
     * @throws EmployeeTaskException
     */
    private function dispatchAutoCloseJob($task, float $newDuration): void
    {
        try {
            $timezone = $task->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
            $timeFrom = CarbonImmutable::parse($task->time_from, $timezone);
            $deadline = $timeFrom->addHours($newDuration);
            $closeAtIso = $deadline->toIso8601String();

            AutoCloseTaskAtDurationExpiryJob::dispatch(
                taskId: $task->id,
                companyId: $task->company_id,
                closeAtIso: $closeAtIso,
            )->delay($deadline);
        } catch (\Exception $e) {
            // Log the error but don't fail the approval
            // The task can still be manually closed if needed
            Log::error('Failed to schedule auto-close job for extension approval', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - the approval still succeeds, just without scheduled close
        }
    }
}

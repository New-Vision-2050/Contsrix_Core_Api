<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\EmployeeTask\Exceptions\EmployeeTaskException;
use Modules\EmployeeTask\Jobs\AutoCloseTaskAtDurationExpiryJob;
use Modules\EmployeeTask\Models\EmployeeTaskExtensionRequest;
use Modules\EmployeeTask\Repositories\EmployeeTaskRepository;
use Modules\ProcedureSetting\Services\ProcedureWorkflowService;

/**
 * Workflow-based approval/rejection for extension requests.
 *
 * This service coordinates extension approval through the procedure workflow system.
 * It uses ProcedureWorkflowService to validate action-takers and step progression,
 * then applies the extension business logic only when the workflow is finalized.
 *
 * Follows the exact same pattern as EmployeeTaskRequestService for consistency.
 */
final class EmployeeTaskExtensionWorkflowService
{
    public function __construct(
        private readonly EmployeeTaskRepository $taskRepository,
        private readonly ProcedureWorkflowService $workflow,
    ) {}

    /**
     * Approve an extension request through the workflow.
     *
     * Extension requests use their own employee_task_extension procedure setting.
     * The extension follows the same approval chain as its parent task.
     *
     * Workflow progression:
     * - Validates current approver via workflow (using parent task's procedure)
     * - Moves to next step if workflow not final
     * - Applies extension business logic only when workflow is final:
     *   - Updates task duration
     *   - Reschedules auto-close job
     *   - Updates extension status
     *   - Records approver info
     *
     * @throws EmployeeTaskException
     */
    public function approve(string $extensionId, string $adminId, ?string $approvalNotes = null): EmployeeTaskExtensionRequest
    {
        $extension = $this->findExtensionOrFail($extensionId);
        $task = $this->findTaskOrFail($extension->employee_task_request_id);

        $this->validateExtensionCanBeResolved($extension);

        // Extension uses parent task's procedure for workflow
        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $result = $this->workflow->advance(
            $extension->current_procedure_step_id,
            $extension->procedure_setting_id,
            $adminId,
            $task->user_id,
            $context,
            processableType: 'employee_task',
            processableId: $task->id,
        );

        return DB::transaction(function () use ($extension, $task, $result, $adminId, $approvalNotes): EmployeeTaskExtensionRequest {
            // If workflow not final, just move to next step
            if (!$result->isFinal) {
                return $extension->update([
                    'current_procedure_step_id' => $result->nextStep->id,
                ]) ? $extension->fresh() : $extension;
            }

            // Workflow complete: apply extension business logic
            if ($task->original_duration_hours === null) {
                $task->update([
                    'original_duration_hours' => $task->duration_hours,
                ]);
            }

            $newDuration = (float) $task->duration_hours + (float) $extension->additional_hours;

            $task->update([
                'duration_hours'         => $newDuration,
                'last_extension_status'  => 'extension_approved',
            ]);

            $extension->update([
                'status'                    => 'approved',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $approvalNotes,
                'current_procedure_step_id' => null,
            ]);

            // Dispatch auto-close job with updated deadline if task has started
            if ($task->time_from) {
                $this->dispatchAutoCloseJob($task, $newDuration);
            }

            return $extension->fresh();
        });
    }

    /**
     * Reject an extension request through the workflow.
     *
     * Rejection always terminates the workflow immediately.
     * Uses extension procedure_setting_id for authorization check.
     * No intermediate steps are traversed.
     *
     * @throws EmployeeTaskException
     */
    public function reject(string $extensionId, string $adminId, string $rejectionReason): EmployeeTaskExtensionRequest
    {
        $extension = $this->findExtensionOrFail($extensionId);
        $task = $this->findTaskOrFail($extension->employee_task_request_id);

        $this->validateExtensionCanBeResolved($extension);

        $context = $task->project_id ? ['project_id' => $task->project_id] : [];
        $this->workflow->assertCanReject($extension->current_procedure_step_id, $adminId, $task->user_id, $context);

        return DB::transaction(function () use ($extension, $task, $adminId, $rejectionReason): EmployeeTaskExtensionRequest {
            $extension->update([
                'status'                    => 'rejected',
                'reviewed_by'               => $adminId,
                'reviewed_at'               => now(),
                'review_notes'              => $rejectionReason,
                'current_procedure_step_id' => null,
            ]);

            $task->update([
                'last_extension_status' => 'extension_rejected',
            ]);

            return $extension->fresh();
        });
    }

    /**
     * Find an extension request or throw exception.
     *
     * @throws EmployeeTaskException
     */
    private function findExtensionOrFail(string $extensionId): EmployeeTaskExtensionRequest
    {
        $extension = EmployeeTaskExtensionRequest::query()
            ->with('currentProcedureStep.actionTakers')
            ->find($extensionId);

        if (!$extension) {
            throw EmployeeTaskException::extensionNotFound();
        }

        return $extension;
    }

    /**
     * Find a task or throw exception.
     *
     * @throws EmployeeTaskException
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
     * Uses the same timezone logic as the extension service.
     *
     * Errors during job dispatch are logged but don't fail the approval — the task
     * can still be manually closed if needed.
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
            Log::error('Failed to schedule auto-close job for extension approval', [
                'task_id' => $task->id,
                'extension_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

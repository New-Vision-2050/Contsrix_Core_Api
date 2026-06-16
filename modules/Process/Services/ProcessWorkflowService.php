<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Events\WorkflowStepActivated;
use Modules\ProcedureSetting\Jobs\AutoApproveWorkflowStep;

use Modules\Process\Enums\ProcessStepStatus;
use Modules\ProcedureSetting\Services\ActionTakerResolver;

class ProcessWorkflowService
{
    public function __construct(
        private readonly ActionTakerResolver $resolver,
    ) {}

    public function createProcessesFromSettings(
            string $processableType,
            string $processableId,
            Collection $settings,
            ?string $createdByUserId = null,
            array $context = [],
        ): ?Process {
            $firstProcess = null;

        foreach ($settings as $index => $setting) {
            /** @var ProcedureSetting $setting */
            $steps = $this->resolveStepsForSetting($setting);

            $snapshots = [];
            foreach ($steps as $step) {
                $resolvedUsers = $this->resolver->resolveUsersForStep($step, $createdByUserId, $context);
                if ($resolvedUsers === []) {
                    continue;
                }
                $snapshots[] = [
                    'step_id'                            => $step->id,
                    'template_step_order'                => $step->step_order,
                    'assigned_user_id'                   => $resolvedUsers[0],
                    'authorized_user_ids'                => $resolvedUsers,
                    'specific_procedure_type'            => $step->action_taker_specific_procedure_type?->value,
                    'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
                ];
            }

            if (empty($snapshots)) {
                continue;
            }

            $sortOrder = $setting->sort_order ?? ($index + 1);

            $exists = Process::query()
                ->where('processable_id', $processableId)
                ->where('processable_type', $processableType)
                ->where('sort_order', $sortOrder)
                ->exists();

            if ($exists) {
                continue;
            }

            $process = Process::create([
                'processable_type'  => $processableType,
                'processable_id'    => $processableId,
                'execute_type'      => $setting->execute_type ?? 'sequence',
                'status'            => $index === 0 ? ProcessStatus::InProgress : ProcessStatus::Pending,
                'template_snapshot' => $snapshots,
                'sort_order'        => $sortOrder,
            ]);

            if ($index === 0) {
                $firstProcess = $process;
                $this->initializeProcessSteps($process);
            }
        }

        return $firstProcess;
    }

    public function initializeProcessSteps(Process $process): void
    {
        if ($process->steps()->exists()) {
            return;
        }

        $snapshot = $process->template_snapshot;
        if (empty($snapshot)) {
            return;
        }

        if ($process->execute_type === 'parallel') {
            foreach ($snapshot as $stepConfig) {
                $this->createProcessStep($process, $stepConfig);
            }
        } else {
            $this->createProcessStep($process, $snapshot[0]);
        }
    }

    private function createProcessStep(Process $process, array $stepConfig): void
    {
        $step = ProcessStep::create([
            'process_id'                         => $process->id,
            'step_id'                            => $stepConfig['step_id'],
            'template_step_order'                => $stepConfig['template_step_order'] ?? null,
            'assigned_user_id'                   => $stepConfig['assigned_user_id'],
            'authorized_user_ids'                => $stepConfig['authorized_user_ids'] ?? null,
            'escalation_management_hierarchy_id' => $stepConfig['escalation_management_hierarchy_id'] ?? null,
            'status'                             => 'pending',
        ]);

        $templateStep = ProcedureSettingStep::query()->find($stepConfig['step_id']);

        if ($templateStep === null) {
            return;
        }

        $authorizedUserIds = $stepConfig['authorized_user_ids'] ?? [$stepConfig['assigned_user_id']];

        // 1. Dispatch centralized notification event (real-time + email + SMS)
        event(new WorkflowStepActivated(
            processStep: $step,
            templateStep: $templateStep,
            userIds: $authorizedUserIds,
            context: [],
        ));

        // 2. Schedule auto-approve if skipping_period is configured
        if (
            $templateStep->requires_approval_within_period
            && $templateStep->skipping_period !== null
            && $templateStep->skipping_period > 0
        ) {
            $delay = Carbon::now()->addHours($templateStep->skipping_period);
            AutoApproveWorkflowStep::dispatch($step->id)->delay($delay);
        }
    }

    private function getSnapshotRowForStep(Process $process, ProcessStep $step): ?array
    {
        $snapshot = $process->template_snapshot ?? [];
        foreach ($snapshot as $row) {
            if ($row['step_id'] === $step->step_id) {
                return $row;
            }
        }
        return null;
    }

    private function getAuthorizedUsersForStep(array $snapshotRow): array
    {
        return $snapshotRow['authorized_user_ids'] ?? [$snapshotRow['assigned_user_id']];
    }
    public function approveStep(string $id): ProcessStep
    {
        return DB::transaction(function () use ($id) {
            $step = ProcessStep::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $process = Process::query()
                ->whereKey($step->process_id)
                ->lockForUpdate()
                ->firstOrFail();

            $snapshotRow = $this->getSnapshotRowForStep($process, $step);
            $authorizedUsers = $step->authorized_user_ids ?? (
                $snapshotRow !== null ? $this->getAuthorizedUsersForStep($snapshotRow) : [(string) $step->assigned_user_id]
            );

            if (! in_array((string) Auth::id(), $authorizedUsers, true)) {
                abort(403);
            }
            if ($step->status->value !== ProcessStepStatus::Pending->value) {
                abort(422, 'Process step is not pending.');
            }

            $step->update([
                'status'    => ProcessStepStatus::Approved,
                'action_by' => Auth::id(),
                'acted_at'  => now(),
            ]);

            $this->advanceProcessAfterAction($process);

            return $step->fresh();
        });
    }

    public function rejectStep(string $id): ProcessStep
    {
        return DB::transaction(function () use ($id) {
            $step = ProcessStep::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $process = Process::query()
                ->whereKey($step->process_id)
                ->lockForUpdate()
                ->firstOrFail();

            $snapshotRow = $this->getSnapshotRowForStep($process, $step);
            $authorizedUsers = $step->authorized_user_ids ?? (
                $snapshotRow !== null ? $this->getAuthorizedUsersForStep($snapshotRow) : [(string) $step->assigned_user_id]
            );

            if (! in_array((string) Auth::id(), $authorizedUsers, true)) {
                abort(403);
            }
            if ($step->status !== ProcessStepStatus::Pending->value) {
                abort(422, 'Process step is not pending.');
            }

            $step->update([
                'status'    => ProcessStepStatus::Rejected,
                'action_by' => Auth::id(),
                'acted_at'  => now(),
            ]);

            $isJobRole = ($snapshotRow['specific_procedure_type'] ?? null) === 'job_role';

            if ($isJobRole) {
                $this->advanceProcessAfterAction($process);
            } else {
                $process->update(['status' => ProcessStatus::Failed]);

                if (method_exists($process->processable, 'onProcessFailed')) {
                    $process->processable->onProcessFailed($process);
                }
            }

            return $step->fresh();
        });
    }

    /**
     * Auto-approve a step without actor authorization check.
     * Used by the skipping_period delayed job.
     */
    public function autoApproveStep(string $id): ProcessStep
    {
        return DB::transaction(function () use ($id) {
            $step = ProcessStep::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $process = Process::query()
                ->whereKey($step->process_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($step->status->value !== ProcessStepStatus::Pending->value) {
                abort(422, 'Process step is not pending.');
            }

            $step->update([
                'status'    => ProcessStepStatus::Approved,
                'action_by' => null,
                'acted_at'  => now(),
            ]);

            $this->advanceProcessAfterAction($process);

            return $step->fresh();
        });
    }

    private function advanceProcessAfterAction(Process $process): void
    {
        if ($process->execute_type === 'sequence') {
            $snapshot = $process->template_snapshot ?? [];
            $approvedCount = $process->steps()
                ->where('status', ProcessStepStatus::Approved)
                ->count();
            $rejectedCount = $process->steps()
                ->where('status', ProcessStepStatus::Rejected)
                ->count();
            $actedCount = $approvedCount + $rejectedCount;

            if ($actedCount < count($snapshot)) {
                $this->createProcessStep($process, $snapshot[$actedCount]);
            } else {
                $process->update(['status' => ProcessStatus::Completed]);
                $this->moveToNextProcessOrFinalize($process);
            }
        } else {
            $total = $process->steps()->count();
            $acted = $process->steps()
                ->whereIn('status', [ProcessStepStatus::Approved, ProcessStepStatus::Rejected])
                ->count();

            if ($acted === $total && $total > 0) {
                $process->update(['status' => ProcessStatus::Completed]);
                $this->moveToNextProcessOrFinalize($process);
            }
        }
    }
    private function moveToNextProcessOrFinalize(Process $currentProcess): void
    {
        $nextProcess = Process::query()
            ->where('processable_id', $currentProcess->processable_id)
            ->where('processable_type', $currentProcess->processable_type)
            ->where('status', ProcessStatus::Pending)
            ->orderBy('sort_order')
            ->first();

        if ($nextProcess) {
            $nextProcess->update(['status' => ProcessStatus::InProgress]);
            $this->initializeProcessSteps($nextProcess);
        } else {
            if (method_exists($currentProcess->processable, 'onAllProcessesCompleted')) {
                $currentProcess->processable->onAllProcessesCompleted($currentProcess);
            }
        }
    }
    public function getCurrentStep(Process $process): ?ProcessStep
    {
        return $process->steps()
            ->where('status', 'pending')
            ->orderBy('template_step_order')
            ->first();
    }

    /**
     * Return steps for the given setting. If the setting has no direct steps,
     * recursively search descendants and return steps from the first child
     * that has any (legacy data pattern support).
     */
    private function resolveStepsForSetting(ProcedureSetting $setting): Collection
    {
        $ids = [$setting->id];
        $ids = array_merge($ids, $this->collectDescendantIds($setting->id));

        return ProcedureSettingStep::query()
            ->with(['actionTakers'])
            ->whereIn('procedure_setting_id', $ids)
            ->orderBy('step_order')
            ->get();
    }

    private function collectDescendantIds(string $parentId): array
    {
        $children = ProcedureSetting::query()
            ->where('parent_id', $parentId)
            ->pluck('id')
            ->all();

        $result = $children;
        foreach ($children as $childId) {
            $result = array_merge($result, $this->collectDescendantIds($childId));
        }

        return $result;
    }
}

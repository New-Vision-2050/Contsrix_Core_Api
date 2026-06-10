<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

use Modules\Process\Enums\ProcessStepStatus;
class ProcessWorkflowService
{
    public function createProcessesFromSettings(
            string $processableType,
            string $processableId,
            Collection $settings,
            ?string $createdByUserId = null,
        ): ?Process {
            $firstProcess = null;

        foreach ($settings as $index => $setting) {
            /** @var ProcedureSetting $setting */
            $steps = ProcedureSettingStep::query()
                ->with(['actionTakers'])
                ->where('procedure_setting_id', $setting->id)
                ->orderBy('step_order')
                ->get();

            $snapshots = [];
            foreach ($steps as $step) {
                $assignedUserId = $this->resolveAssignedUserId($step, $createdByUserId);
                if ($assignedUserId === null) {
                    continue;
                }
                $snapshots[] = [
                    'step_id'                            => $step->id,
                    'template_step_order'                => $step->step_order,
                    'assigned_user_id'                   => $assignedUserId,
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
        ProcessStep::create([
            'process_id'                         => $process->id,
            'step_id'                            => $stepConfig['step_id'],
            'template_step_order'                => $stepConfig['template_step_order'] ?? null,
            'assigned_user_id'                   => $stepConfig['assigned_user_id'],
            'escalation_management_hierarchy_id' => $stepConfig['escalation_management_hierarchy_id'] ?? null,
            'status'                             => 'pending',
        ]);
    }

    private function resolveAssignedUserId(ProcedureSettingStep $step, ?string $createdByUserId = null): ?string
    {
        $actionTakerType = $step->action_taker_type?->value ?? 'specific_user';

        if ($actionTakerType === 'management_hierarchy' && $createdByUserId !== null) {
            return $this->resolveManagerFromCreatorHierarchy($step, $createdByUserId);
        }

        $taker = $step->actionTakers->first();
        return $taker ? (string) $taker->user_id : null;
    }

    private function resolveManagerFromCreatorHierarchy(ProcedureSettingStep $step, string $createdByUserId): ?string
    {
        $hierarchyType = $step->action_taker_management_hierarchy_type?->value;

        if ($hierarchyType === null) {
            return null;
        }

        $creator = \Modules\User\Models\User::query()
            ->with('professionalData')
            ->find($createdByUserId);

        if ($creator === null) {
            return null;
        }

        $professionalData = $creator->professionalData;

        if ($professionalData === null) {
            return null;
        }

        $hierarchyId = null;
        if ($hierarchyType === 'branch_manager') {
            $hierarchyId = $professionalData->branch_id;
        } elseif ($hierarchyType === 'management_manager') {
            $hierarchyId = $professionalData->management_id;
        }

        if ($hierarchyId === null) {
            return null;
        }

        $hierarchy = \Modules\Company\ManagementHierarchy\Models\ManagementHierarchy::query()
            ->find($hierarchyId);

        if ($hierarchy === null || $hierarchy->manager_id === null) {
            return null;
        }

        return (string) $hierarchy->manager_id;
    }
    public function approveStep(string $id): ProcessStep
    {
        return DB::transaction(function () use ($id) {
            $step = ProcessStep::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();
// dd($step);
            $process = Process::query()
                ->whereKey($step->process_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((string) Auth::id() !== (string) $step->assigned_user_id) {
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

            if ($process->execute_type === 'sequence') {
                $snapshot = $process->template_snapshot ?? [];
                $approvedCount = $process->steps()
                    ->where('status', ProcessStepStatus::Approved)
                    ->count();

                if ($approvedCount < count($snapshot)) {
                    $this->createProcessStep($process, $snapshot[$approvedCount]);
                } else {
                    $process->update(['status' => ProcessStatus::Completed]);
                    $this->moveToNextProcessOrFinalize($process);
                }
            } else {
                $total = $process->steps()->count();
                $approved = $process->steps()
                    ->where('status', ProcessStepStatus::Approved)
                    ->count();

                if ($approved === $total && $total > 0) {
                    $process->update(['status' => ProcessStatus::Completed]);
                    $this->moveToNextProcessOrFinalize($process);
                }
            }

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

            if ((string) Auth::id() !== (string) $step->assigned_user_id) {
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

            $process->update(['status' => ProcessStatus::Failed]);

            if (method_exists($process->processable, 'onProcessFailed')) {
                $process->processable->onProcessFailed($process);
            }

            return $step->fresh();
        });
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
}

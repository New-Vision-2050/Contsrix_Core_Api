<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Events\ProcessStepPending;
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
                $actionTakerIds = $this->resolveActionTakerIds($step);
                if (empty($actionTakerIds)) {
                    continue;
                }
                $assignedUserId = $actionTakerIds[0];
                $snapshots[] = [
                    'step_id'                           => $step->id,
                    'template_step_order'               => $step->step_order,
                    'notify_by_sms'                     => $step->notify_by_sms,
                    'notify_by_email'                   => $step->notify_by_email,
                    'notify_by_whatsapp'                => $step->notify_by_whatsapp,
                    'auto_approval_within_hours'        => $step->auto_approval_within_hours,
                    'is_view_only'                      => $step->is_view_only,
                    'is_return_with_notes'              => $step->is_return_with_notes,
                    'approval_within_days'              => $step->approval_within_days,
                    'approval_within_hours'             => $step->approval_within_hours,
                    'assigned_user_id'                   => $assignedUserId,
                    'escalation_management_hierarchy_id' => $step->escalation_management_hierarchy_id,
                    'action_taker_ids'                   => $actionTakerIds,
                ];
            }
            if (empty($snapshots)) {
                continue;
            }

            $process = Process::create([
                'processable_type'  => $processableType,
                'processable_id'    => $processableId,
                'execute_type'      => $setting->execute_type ?? 'sequence',
                'status'            => $index === 0 ? ProcessStatus::InProgress : ProcessStatus::Pending,
                'template_snapshot' => $snapshots,
                'sort_order'        => $setting->sort_order ?? ($index + 1),
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
            'notify_by_sms'                     => $stepConfig['notify_by_sms'] ?? false,
            'notify_by_email'                   => $stepConfig['notify_by_email'] ?? false,
            'notify_by_whatsapp'                => $stepConfig['notify_by_whatsapp'] ?? false,
            'auto_approval_within_hours'        => $stepConfig['auto_approval_within_hours'] ?? null,
            'is_view_only'                      => $stepConfig['is_view_only'] ?? false,
            'is_return_with_notes'              => $stepConfig['is_return_with_notes'] ?? false,
            'approval_within_days'              => $stepConfig['approval_within_days'] ?? null,
            'approval_within_hours'             => $stepConfig['approval_within_hours'] ?? null,
            'assigned_user_id'                   => $stepConfig['assigned_user_id'],
            'escalation_management_hierarchy_id' => $stepConfig['escalation_management_hierarchy_id'] ?? null,
            'status'                             => 'pending',
        ]);

        $actionTakerIds = $stepConfig['action_taker_ids'] ?? [$stepConfig['assigned_user_id']];
        foreach ($actionTakerIds as $userId) {
            \Modules\Process\Models\ProcessStepActionTaker::create([
                'process_step_id' => $step->id,
                'user_id'         => $userId,
            ]);
        }

        if ($process->status === ProcessStatus::InProgress) {
            event(new ProcessStepPending($step));
        }
    }

    private function resolveActionTakerIds(ProcedureSettingStep $step): array
    {
        return $step->actionTakers
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
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

<?php

declare(strict_types=1);

namespace Modules\Process\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Models\Process;
use Modules\Process\Models\ProcessStep;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;

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
                $assignedUserId = $this->resolveAssignedUserId($step);
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
        ProcessStep::create([
            'process_id'                         => $process->id,
            'step_id'                            => $stepConfig['step_id'],
            'template_step_order'                => $stepConfig['template_step_order'] ?? null,
            'assigned_user_id'                   => $stepConfig['assigned_user_id'],
            'escalation_management_hierarchy_id' => $stepConfig['escalation_management_hierarchy_id'] ?? null,
            'status'                             => 'pending',
        ]);
    }

    private function resolveAssignedUserId(ProcedureSettingStep $step): ?string
    {
        $taker = $step->actionTakers->first();
        return $taker ? (string) $taker->user_id : null;
    }

    public function getCurrentStep(Process $process): ?ProcessStep
    {
        return $process->steps()
            ->where('status', 'pending')
            ->orderBy('template_step_order')
            ->first();
    }
}

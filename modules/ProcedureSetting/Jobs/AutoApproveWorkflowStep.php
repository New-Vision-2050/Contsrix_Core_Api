<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Process\Models\ProcessStep;
use Modules\Process\Services\ProcessWorkflowService;

class AutoApproveWorkflowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $processStepId,
    ) {}

    public function handle(ProcessWorkflowService $workflow): void
    {
        $step = ProcessStep::query()->find($this->processStepId);

        if ($step === null) {
            Log::warning('AutoApproveWorkflowStep: ProcessStep not found', [
                'process_step_id' => $this->processStepId,
            ]);

            return;
        }

        if ($step->status->value !== 'pending') {
            Log::info('AutoApproveWorkflowStep: Step already acted on, skipping auto-approve', [
                'process_step_id' => $this->processStepId,
                'status' => $step->status->value,
            ]);

            return;
        }

        Log::info('AutoApproveWorkflowStep: Auto-approving step after skipping period', [
            'process_step_id' => $this->processStepId,
        ]);

        try {
            $workflow->autoApproveStep($step->id);
        } catch (\Throwable $e) {
            Log::error('AutoApproveWorkflowStep: Auto-approve failed', [
                'process_step_id' => $this->processStepId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

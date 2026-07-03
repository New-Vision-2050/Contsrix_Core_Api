<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Presenters;

use Modules\ProcedureSetting\Models\InternalProcedureTaken;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Process\Models\Process;
use Modules\Process\Enums\ProcessStepStatus;
use Modules\Process\Enums\ProcessStatus;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\Media\Presenters\MediaPresenter;

final class TaskProcedurePresenter
{
    /**
     * @param list<CustomMedia> $attachments
     */
    public function __construct(
        private readonly ?InternalProcedureTaken $taken,
        private readonly int $stepNumber,
        private readonly ?Process $process = null,
        private readonly array $attachments = [],
        private readonly ?array $formData = null,
    ) {}

    public function toArray(): array
    {
        $t       = $this->taken;
        $setting = $t?->procedureSetting ?? $this->process?->procedureSetting;
        $user    = $t?->takenByUser;

        return [
            'id'          => $t?->id,
            'step_number' => $this->stepNumber,
            'name'        => $setting?->name,
            'icon'        => $setting?->icon,
            'percentage'  => $setting?->percentage,
            'form'        => $t?->form ?? $setting?->form,
            'taken_by'    => $user ? [
                'id'   => $user->id,
                'name' => $user->name,
            ] : null,
            'taken_at'    => $t?->taken_at?->format('Y-m-d H:i:s'),
            'status'      => $this->presentStatus(),
            'steps'       => $this->presentSteps(),
            'approved_by' => $this->presentApprovedBy(),
            'attachments' => $this->presentAttachments(),
            'form_data'   => $this->formData,
        ];
    }

    private function presentStatus(): ?string
    {
        return $this->process?->status?->value;
    }

    /**
     * @return list<array{step_order: int, name: ?string, status: string, action_by: ?array{id: string, name: string}, acted_at: ?string}>
     */
    private function presentSteps(): array
    {
        if (! $this->process || ! $this->process->relationLoaded('steps')) {
            return [];
        }

        $steps = [];
        foreach ($this->process->steps as $step) {
            $actionByUser = $step->relationLoaded('actionByUser') ? $step->actionByUser : null;
            $procedureStep = $step->relationLoaded('procedureSettingStep') ? $step->procedureSettingStep : null;

            $steps[] = [
                'step_order' => $step->template_step_order,
                'name'       => $procedureStep?->name,
                'status'     => $step->status?->value,
                'action_by'  => $actionByUser ? [
                    'id'   => $actionByUser->id,
                    'name' => $actionByUser->name,
                ] : null,
                'acted_at'   => $step->acted_at?->format('Y-m-d H:i:s'),
            ];
        }

        return $steps;
    }

    /**
     * The user who approved the final step of the workflow.
     */
    private function presentApprovedBy(): ?array
    {
        if (! $this->process || ! $this->process->relationLoaded('steps')) {
            return null;
        }

        $approvedStep = $this->process->steps
            ->where('status', ProcessStepStatus::Approved)
            ->sortByDesc('acted_at')
            ->first();

        if (! $approvedStep) {
            return null;
        }

        $user = $approvedStep->relationLoaded('actionByUser') ? $approvedStep->actionByUser : null;

        return $user ? [
            'id'   => $user->id,
            'name' => $user->name,
        ] : null;
    }

    private function presentAttachments(): array
    {
        if ($this->attachments === []) {
            return [];
        }

        return MediaPresenter::collection($this->attachments);
    }

    /**
     * @param list<array{taken: ?InternalProcedureTaken, process: ?Process, attachments: list<CustomMedia>, form_data: ?array}> $items
     */
    public static function collection(iterable $items): array
    {
        $result = [];
        $step   = 1;
        foreach ($items as $item) {
            $result[] = (new self(
                taken: $item['taken'] ?? null,
                stepNumber: $step++,
                process: $item['process'] ?? null,
                attachments: $item['attachments'] ?? [],
                formData: $item['form_data'] ?? null,
            ))->toArray();
        }
        return $result;
    }
}

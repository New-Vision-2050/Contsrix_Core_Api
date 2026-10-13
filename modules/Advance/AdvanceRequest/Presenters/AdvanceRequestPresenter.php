<?php

declare(strict_types=1);

namespace Modules\Advance\AdvanceRequest\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Advance\AdvanceRequest\Models\AdvanceRequest;
use Modules\Process\Enums\ProcessStatus;
use Modules\Process\Enums\ProcessStepStatus;

class AdvanceRequestPresenter extends AbstractPresenter
{
    public function __construct(private readonly AdvanceRequest $advanceRequest)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $activeProcess = $this->advanceRequest->relationLoaded('processes')
            ? $this->advanceRequest->processes
                ->where('status', ProcessStatus::InProgress)
                ->sortBy('sort_order')
                ->first()
            : null;

        $pendingStep = $activeProcess !== null
            ? $activeProcess->steps->where('status', ProcessStepStatus::Pending)->sortBy('template_step_order')->first()
            : null;

        return [
            'id' => (string) $this->advanceRequest->id,
            'user_id' => (string) $this->advanceRequest->user_id,
            'user_name' => $this->advanceRequest->relationLoaded('user') ? $this->advanceRequest->user?->name : null,
            'advance_policy_id' => (string) $this->advanceRequest->advance_policy_id,
            'advance_policy_name' => $this->advanceRequest->advancePolicy?->name,
            'amount' => (float) $this->advanceRequest->amount,
            'installments' => $this->advanceRequest->installments,
            'reason' => $this->advanceRequest->reason,
            'status' => $this->advanceRequest->status,
            'status_label_ar' => $this->statusLabelAr(),
            'attachments' => $this->advanceRequest->attachments,
            'notes' => $this->advanceRequest->notes,
            'rejection_reason' => $this->advanceRequest->rejection_reason,
            'approved_at' => $this->advanceRequest->approved_at?->toDateTimeString(),
            'rejected_at' => $this->advanceRequest->rejected_at?->toDateTimeString(),
            'workflow' => [
                'in_progress' => $activeProcess !== null,
                'pending_step_order' => $pendingStep?->template_step_order,
            ],
            'created_at' => $this->advanceRequest->created_at?->toDateTimeString(),
        ];
    }

    private function statusLabelAr(): string
    {
        return match ($this->advanceRequest->status) {
            AdvanceRequest::STATUS_PENDING => 'قيد الانتظار',
            AdvanceRequest::STATUS_APPROVED => 'مقبول',
            AdvanceRequest::STATUS_REJECTED => 'مرفوض',
            AdvanceRequest::STATUS_CANCELLED => 'ملغي',
            default => (string) $this->advanceRequest->status,
        };
    }
}

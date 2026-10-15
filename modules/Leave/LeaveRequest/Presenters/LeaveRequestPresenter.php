<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveRequest\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\LeaveRequest;
use Modules\Process\Enums\ProcessStatus;

class LeaveRequestPresenter extends AbstractPresenter
{
    public function __construct(private readonly LeaveRequest $leaveRequest)
    {
    }

    protected function present(bool $isListing = false): array
    {
        $policy = $this->leaveRequest->leavePolicy;

        $activeProcess = $this->leaveRequest->relationLoaded('processes')
            ? $this->leaveRequest->processes
                ->where('status', ProcessStatus::InProgress)
                ->sortBy('sort_order')
                ->first()
            : null;

        $pendingStep = $activeProcess !== null
            ? $activeProcess->steps->where('status', \Modules\Process\Enums\ProcessStepStatus::Pending)->sortBy('template_step_order')->first()
            : null;

        return [
            'id' => (string) $this->leaveRequest->id,
            'user_id' => (string) $this->leaveRequest->user_id,
            'user_name' => $this->leaveRequest->relationLoaded('user') ? $this->leaveRequest->user?->name : null,
            'leave_policy_id' => $this->leaveRequest->leave_policy_id !== null ? (string) $this->leaveRequest->leave_policy_id : null,
            'leave_policy_name' => $policy?->name,
            'leave_type_id' => (string) $this->leaveRequest->leave_type_id,
            'leave_type_name' => $policy?->leaveType?->name ?? $this->leaveRequest->leaveType?->name,
            'start_date' => $this->leaveRequest->start_date?->toDateString(),
            'end_date' => $this->leaveRequest->end_date?->toDateString(),
            'total_days' => $this->leaveRequest->total_days,
            'reason' => $this->leaveRequest->reason,
            'status' => $this->leaveRequest->status,
            'status_label_ar' => $this->statusLabelAr(),
            'is_emergency' => $this->leaveRequest->is_emergency ? 1 : 0,
            'attachments' => $this->leaveRequest->attachments,
            'notes' => $this->leaveRequest->notes,
            'rejection_reason' => $this->leaveRequest->rejection_reason,
            'approved_at' => $this->leaveRequest->approved_at?->toDateTimeString(),
            'rejected_at' => $this->leaveRequest->rejected_at?->toDateTimeString(),
            'workflow' => [
                'in_progress' => $activeProcess !== null,
                'pending_step_order' => $pendingStep?->template_step_order,
            ],
            'created_at' => $this->leaveRequest->created_at?->toDateTimeString(),
        ];
    }

    private function statusLabelAr(): string
    {
        return match ($this->leaveRequest->status) {
            LeaveRequest::STATUS_PENDING => 'قيد الانتظار',
            LeaveRequest::STATUS_APPROVED => 'مقبول',
            LeaveRequest::STATUS_REJECTED => 'مرفوض',
            LeaveRequest::STATUS_CANCELLED => 'ملغي',
            default => (string) $this->leaveRequest->status,
        };
    }
}

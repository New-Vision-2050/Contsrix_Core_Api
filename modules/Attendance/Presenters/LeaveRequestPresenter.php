<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\LeaveRequest;

class LeaveRequestPresenter extends AbstractPresenter
{
    public function __construct(private LeaveRequest $leaveRequest)
    {
        parent::__construct($leaveRequest);
    }

    public function getData(): array
    {
        return [
            'id' => $this->leaveRequest->id,
            'user_id' => $this->leaveRequest->user_id,
            'company_id' => $this->leaveRequest->company_id,
            'leave_type_id' => $this->leaveRequest->leave_type_id,

            // Leave dates
            'start_date' => $this->leaveRequest->start_date?->format('Y-m-d'),
            'end_date' => $this->leaveRequest->end_date?->format('Y-m-d'),
            'total_days' => $this->leaveRequest->total_days,

            // Request details
            'reason' => $this->leaveRequest->reason,
            'is_emergency' => $this->leaveRequest->is_emergency,
            'contact_info' => $this->leaveRequest->contact_info,
            'attachments' => $this->leaveRequest->attachments,

            // Status and approval
            'status' => $this->leaveRequest->status,
            'approved_by' => $this->leaveRequest->approved_by,
            'approved_at' => $this->leaveRequest->approved_at?->format('Y-m-d H:i:s'),
            'rejection_reason' => $this->leaveRequest->rejection_reason,

            // Timestamps
            'created_at' => $this->leaveRequest->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->leaveRequest->updated_at?->format('Y-m-d H:i:s'),

            // Relationships
            'user' => $this->leaveRequest->user ? [
                'id' => $this->leaveRequest->user->id,
                'name' => $this->leaveRequest->user->name,
                'email' => $this->leaveRequest->user->email,
                'employee_id' => $this->leaveRequest->user->employee_id ?? null,
            ] : null,

            'company' => $this->leaveRequest->company ? [
                'id' => $this->leaveRequest->company->id,
                'name' => $this->leaveRequest->company->name,
            ] : null,

            'leave_type' => $this->leaveRequest->leaveType ? [
                'id' => $this->leaveRequest->leaveType->id,
                'name' => $this->leaveRequest->leaveType->name,
                'code' => $this->leaveRequest->leaveType->code,
                'color' => $this->leaveRequest->leaveType->color,
                'max_days_per_year' => $this->leaveRequest->leaveType->max_days_per_year,
                'requires_approval' => $this->leaveRequest->leaveType->requires_approval,
            ] : null,

            'approved_by_user' => $this->leaveRequest->approvedBy ? [
                'id' => $this->leaveRequest->approvedBy->id,
                'name' => $this->leaveRequest->approvedBy->name,
            ] : null,

            // Computed properties
            'duration_text' => $this->getDurationText(),
            'status_text' => $this->getStatusText(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_modified' => $this->canBeModified(),
            'is_past_due' => $this->isPastDue(),
            'days_until_start' => $this->getDaysUntilStart(),
        ];
    }

    /**
     * Get human-readable duration text
     */
    private function getDurationText(): string
    {
        if (!$this->leaveRequest->total_days) {
            return '0 days';
        }

        $days = $this->leaveRequest->total_days;

        if ($days == 1) {
            return '1 day';
        }

        if ($days < 7) {
            return "{$days} days";
        }

        $weeks = floor($days / 7);
        $remainingDays = $days % 7;

        $text = $weeks == 1 ? '1 week' : "{$weeks} weeks";

        if ($remainingDays > 0) {
            $text .= $remainingDays == 1 ? ' 1 day' : " {$remainingDays} days";
        }

        return $text;
    }

    /**
     * Get human-readable status text
     */
    private function getStatusText(): string
    {
        return match($this->leaveRequest->status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->leaveRequest->status)
        };
    }

    /**
     * Check if leave request can be cancelled
     */
    private function canBeCancelled(): bool
    {
        return in_array($this->leaveRequest->status, ['pending', 'approved'])
            && $this->leaveRequest->start_date
            && $this->leaveRequest->start_date->isFuture();
    }

    /**
     * Check if leave request can be modified
     */
    private function canBeModified(): bool
    {
        return $this->leaveRequest->status === 'pending'
            && $this->leaveRequest->start_date
            && $this->leaveRequest->start_date->isFuture();
    }

    /**
     * Check if leave request is past due
     */
    private function isPastDue(): bool
    {
        return $this->leaveRequest->end_date
            && $this->leaveRequest->end_date->isPast();
    }

    /**
     * Get days until leave starts
     */
    private function getDaysUntilStart(): ?int
    {
        if (!$this->leaveRequest->start_date || $this->leaveRequest->start_date->isPast()) {
            return null;
        }

        return now()->diffInDays($this->leaveRequest->start_date);
    }

    /**
     * Get summary data for calendar view
     */
    public function getCalendarData(): array
    {
        return [
            'id' => $this->leaveRequest->id,
            'title' => $this->leaveRequest->user?->name . ' - ' . $this->leaveRequest->leaveType?->name,
            'start' => $this->leaveRequest->start_date?->format('Y-m-d'),
            'end' => $this->leaveRequest->end_date?->addDay()->format('Y-m-d'), // FullCalendar end is exclusive
            'color' => $this->leaveRequest->leaveType?->color ?? '#3788d8',
            'status' => $this->leaveRequest->status,
            'is_emergency' => $this->leaveRequest->is_emergency,
            'user_name' => $this->leaveRequest->user?->name,
            'leave_type' => $this->leaveRequest->leaveType?->name,
        ];
    }

    /**
     * Get minimal data for reports
     */
    public function getReportData(): array
    {
        return [
            'employee_name' => $this->leaveRequest->user?->name,
            'employee_id' => $this->leaveRequest->user?->employee_id,
            'leave_type' => $this->leaveRequest->leaveType?->name,
            'start_date' => $this->leaveRequest->start_date?->format('Y-m-d'),
            'end_date' => $this->leaveRequest->end_date?->format('Y-m-d'),
            'total_days' => $this->leaveRequest->total_days,
            'status' => $this->getStatusText(),
            'is_emergency' => $this->leaveRequest->is_emergency ? 'Yes' : 'No',
            'reason' => $this->leaveRequest->reason,
            'approved_by' => $this->leaveRequest->approvedBy?->name,
            'approved_at' => $this->leaveRequest->approved_at?->format('Y-m-d h:i:s A'),
            'created_at' => $this->leaveRequest->created_at?->format('Y-m-d h:i:s A'),
        ];
    }
}

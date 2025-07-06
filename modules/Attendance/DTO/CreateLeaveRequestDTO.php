<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class CreateLeaveRequestDTO
{
    public function __construct(
        public readonly string $user_id,
        public readonly string $company_id,
        public readonly string $leave_type_id,
        public readonly string $start_date,
        public readonly string $end_date,
        public readonly ?string $reason = null,
        public readonly bool $is_emergency = false,
        public readonly ?array $attachments = null,
        public readonly ?string $contact_info = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'leave_type_id' => $this->leave_type_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'reason' => $this->reason,
            'is_emergency' => $this->is_emergency,
            'attachments' => $this->attachments,
            'contact_info' => $this->contact_info,
        ];
    }

    public function getUserId(): string
    {
        return $this->user_id;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getLeaveTypeId(): string
    {
        return $this->leave_type_id;
    }

    public function getStartDate(): string
    {
        return $this->start_date;
    }

    public function getEndDate(): string
    {
        return $this->end_date;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function isEmergency(): bool
    {
        return $this->is_emergency;
    }

    public function getAttachments(): ?array
    {
        return $this->attachments;
    }

    public function getContactInfo(): ?string
    {
        return $this->contact_info;
    }
}

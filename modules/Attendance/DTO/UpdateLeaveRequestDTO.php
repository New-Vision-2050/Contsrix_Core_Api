<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class UpdateLeaveRequestDTO
{
    public function __construct(
        public readonly ?string $leave_type_id = null,
        public readonly ?string $start_date = null,
        public readonly ?string $end_date = null,
        public readonly ?string $reason = null,
        public readonly ?bool $is_emergency = null,
        public readonly ?array $attachments = null,
        public readonly ?string $contact_info = null,
    ) {}

    public function toArray(): array
    {
        $data = [];
        
        if ($this->leave_type_id !== null) {
            $data['leave_type_id'] = $this->leave_type_id;
        }
        
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }
        
        if ($this->reason !== null) {
            $data['reason'] = $this->reason;
        }
        
        if ($this->is_emergency !== null) {
            $data['is_emergency'] = $this->is_emergency;
        }
        
        if ($this->attachments !== null) {
            $data['attachments'] = $this->attachments;
        }
        
        if ($this->contact_info !== null) {
            $data['contact_info'] = $this->contact_info;
        }
        
        return $data;
    }

    public function getLeaveTypeId(): ?string
    {
        return $this->leave_type_id;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function isEmergency(): ?bool
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

    public function hasChanges(): bool
    {
        return !empty($this->toArray());
    }
}

<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class FilterViolationDTO
{
    public function __construct(
        public string $company_id,
        public ?string $user_id = null,
        public ?string $constraint_id = null,
        public ?string $severity = null,
        public ?string $status = null,
        public ?string $violation_type = null,
        public ?string $detected_from = null,
        public ?string $detected_to = null,
        public ?string $resolved_by = null,
        public ?string $user_name = null,
        public ?string $user_email = null,
        public ?string $constraint_name = null,
        public ?bool $critical = null,
        public ?int $page = null,
        public ?int $per_page = null,
    ) {}

    public function toArray(): array
    {
        $data = ['company_id' => $this->company_id];
        
        if ($this->user_id !== null) {
            $data['user_id'] = $this->user_id;
        }
        if ($this->constraint_id !== null) {
            $data['constraint_id'] = $this->constraint_id;
        }
        if ($this->severity !== null) {
            $data['severity'] = $this->severity;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->violation_type !== null) {
            $data['violation_type'] = $this->violation_type;
        }
        if ($this->detected_from !== null) {
            $data['detected_from'] = $this->detected_from;
        }
        if ($this->detected_to !== null) {
            $data['detected_to'] = $this->detected_to;
        }
        if ($this->resolved_by !== null) {
            $data['resolved_by'] = $this->resolved_by;
        }
        if ($this->user_name !== null) {
            $data['user_name'] = $this->user_name;
        }
        if ($this->user_email !== null) {
            $data['user_email'] = $this->user_email;
        }
        if ($this->constraint_name !== null) {
            $data['constraint_name'] = $this->constraint_name;
        }
        if ($this->critical !== null) {
            $data['critical'] = $this->critical;
        }

        return $data;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getConstraintId(): ?string
    {
        return $this->constraint_id;
    }

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getViolationType(): ?string
    {
        return $this->violation_type;
    }

    public function getDetectedFrom(): ?string
    {
        return $this->detected_from;
    }

    public function getDetectedTo(): ?string
    {
        return $this->detected_to;
    }

    public function getResolvedBy(): ?string
    {
        return $this->resolved_by;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function getConstraintName(): ?string
    {
        return $this->constraint_name;
    }

    public function isCritical(): ?bool
    {
        return $this->critical;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPerPage(): ?int
    {
        return $this->per_page;
    }
}

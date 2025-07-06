<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class FilterStatisticsDTO
{
    public function __construct(
        public string $company_id,
        public ?string $constraint_type = null,
        public ?string $constraint_name = null,
        public ?string $user_id = null,
        public ?string $department_id = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
    ) {}

    public function toArray(): array
    {
        $data = ['company_id' => $this->company_id];
        
        if ($this->constraint_type !== null) {
            $data['constraint_type'] = $this->constraint_type;
        }
        if ($this->constraint_name !== null) {
            $data['constraint_name'] = $this->constraint_name;
        }
        if ($this->user_id !== null) {
            $data['user_id'] = $this->user_id;
        }
        if ($this->department_id !== null) {
            $data['department_id'] = $this->department_id;
        }
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }

        return $data;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getConstraintType(): ?string
    {
        return $this->constraint_type;
    }

    public function getConstraintName(): ?string
    {
        return $this->constraint_name;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }
}

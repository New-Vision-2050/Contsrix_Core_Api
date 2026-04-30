<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class FilterAttendanceConstraintDTO
{
    public function __construct(
        public string $company_id,
        public ?string $constraint_type = null,
        public ?string $name = null,
        public ?string $user_id = null,
        public ?string $department_id = null,
        public ?int $branch_id = null,
        public ?string $branch_name = null,
        public ?int $priority_from = null,
        public ?int $priority_to = null,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
        public ?string $user_name = null,
        public ?string $user_email = null,
        public ?string $company_name = null,
        public ?bool $is_active = null,
        public ?int $employee_status = 1,
        // public ?int $page = 1,
        // public ?int $per_page = 10,
    ) {}

    public function toArray(): array
    {
        $data = ['company_id' => $this->company_id];

        if ($this->constraint_type !== null) {
            $data['constraint_type'] = $this->constraint_type;
        }
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->user_id !== null) {
            $data['user_id'] = $this->user_id;
        }
        if ($this->department_id !== null) {
            $data['department_id'] = $this->department_id;
        }
        if ($this->branch_id !== null) {
            $data['branch_id'] = $this->branch_id;
        }
        if ($this->branch_name !== null) {
            $data['branch_name'] = $this->branch_name;
        }
        if ($this->priority_from !== null) {
            $data['priority_from'] = $this->priority_from;
        }
        if ($this->priority_to !== null) {
            $data['priority_to'] = $this->priority_to;
        }
        if ($this->effective_from !== null) {
            $data['effective_from'] = $this->effective_from;
        }
        if ($this->effective_to !== null) {
            $data['effective_to'] = $this->effective_to;
        }
        if ($this->user_name !== null) {
            $data['user_name'] = $this->user_name;
        }
        if ($this->user_email !== null) {
            $data['user_email'] = $this->user_email;
        }
        if ($this->company_name !== null) {
            $data['company_name'] = $this->company_name;
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        if ($this->employee_status !== null) {
            $data['employee_status'] = $this->employee_status;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getBranchId(): ?int
    {
        return $this->branch_id;
    }

    public function getBranchName(): ?string
    {
        return $this->branch_name;
    }

    public function getPriorityFrom(): ?int
    {
        return $this->priority_from;
    }

    public function getPriorityTo(): ?int
    {
        return $this->priority_to;
    }

    public function getEffectiveFrom(): ?string
    {
        return $this->effective_from;
    }

    public function getEffectiveTo(): ?string
    {
        return $this->effective_to;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function getCompanyName(): ?string
    {
        return $this->company_name;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function getEmployeeStatus(): ?int
    {
        return $this->employee_status;
    }

    // public function getPage(): ?int
    // {
    //     return $this->page;
    // }

    // public function getPerPage(): ?int
    // {
    //     return $this->per_page;
    // }
}

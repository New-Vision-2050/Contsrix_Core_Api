<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class UpdateAttendanceConstraintDTO
{
    public function __construct(
        public string $updated_by,
        public ?string $constraint_type = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?array $config = null,
        public ?string $user_id = null,
        public ?string $department_id = null,
        public ?int $priority = null,
        public ?bool $is_active = null,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
    ) {}

    public function toArray(): array
    {
        $data = ['updated_by' => $this->updated_by];
        
        if ($this->constraint_type !== null) {
            $data['constraint_type'] = $this->constraint_type;
        }
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->config !== null) {
            $data['config'] = $this->config;
        }
        if ($this->user_id !== null) {
            $data['user_id'] = $this->user_id;
        }
        if ($this->department_id !== null) {
            $data['department_id'] = $this->department_id;
        }
        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        if ($this->effective_from !== null) {
            $data['effective_from'] = $this->effective_from;
        }
        if ($this->effective_to !== null) {
            $data['effective_to'] = $this->effective_to;
        }

        return $data;
    }

    public function getUpdatedBy(): string
    {
        return $this->updated_by;
    }

    public function getConstraintType(): ?string
    {
        return $this->constraint_type;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function getEffectiveFrom(): ?string
    {
        return $this->effective_from;
    }

    public function getEffectiveTo(): ?string
    {
        return $this->effective_to;
    }
}

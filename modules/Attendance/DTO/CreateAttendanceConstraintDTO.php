<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class CreateAttendanceConstraintDTO
{
    public function __construct(
        public string $constraint_type,
        public string $name,
        public string $description,
        public array $config,
        public string $company_id,
        public string $created_by,
        public ?string $user_id = null,
        public ?string $department_id = null,
        public ?int $priority = 1,
        public bool $is_active = true,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
    ) {}

    public function toArray(): array
    {
        return [
            'constraint_type' => $this->constraint_type,
            'name' => $this->name,
            'description' => $this->description,
            'config' => $this->config,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
        ];
    }

    public function getConstraintType(): string
    {
        return $this->constraint_type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isActive(): bool
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

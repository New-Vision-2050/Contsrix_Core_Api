<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;
use Ramsey\Uuid\UuidInterface;
class CreateAttendanceConstraintDTO
{
    public function __construct(
        public string $constraint_type,
        public string $name,
        public ?string $notes = null,
        public ?array $config = [],
        public UuidInterface $company_id,
        public UuidInterface $created_by,
        public ?array $user_ids = [],
        public ?array $department_ids = [],
        public ?array $branch_ids = [],
        public ?array $branch_locations = [],
        public int $priority = 1,
        public bool $is_active = true,
        public bool $inherit_from_parent = false,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
        public ?int $max_over_time = null,
    ) {}

    public function toArray(): array
    {
        return [
            'constraint_type' => $this->constraint_type,
            'constraint_name' => $this->name,
            'notes' => $this->notes,
            'constraint_config' => $this->config,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'user_ids' => $this->user_ids,
            'department_ids' => $this->department_ids,
            'branch_ids' => $this->branch_ids,
            'branch_locations' => $this->branch_locations,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'inherit_from_parent' => $this->inherit_from_parent,
            'start_date' => $this->effective_from,
            'end_date' => $this->effective_to,
            'max_over_time' => $this->max_over_time,
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->company_id;
    }

    public function getCreatedBy(): UuidInterface
    {
        return $this->created_by;
    }

    public function getUserIds(): ?array
    {
        return $this->user_ids;
    }

    public function getDepartmentIds(): ?array
    {
        return $this->department_ids;
    }

    public function getBranchIds(): ?array
    {
        return $this->branch_ids;
    }

    public function getBranchLocations(): ?array
    {
        return $this->branch_locations;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isInheritFromParent(): bool
    {
        return $this->inherit_from_parent;
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

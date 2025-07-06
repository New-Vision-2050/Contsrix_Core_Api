<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;
use Ramsey\Uuid\UuidInterface;
class CreateAttendanceConstraintDTO
{
    public function __construct(
        public string $constraint_type,
        public string $name,
        public string $description,
        public array $config,
        public UuidInterface $company_id,
        public UuidInterface $created_by,
        public ?string $user_id = null,
        public ?string $department_id = null,
        public ?array $branch_ids = null,
        public ?array $branch_locations = null,
        public ?int $priority = 1,
        public bool $is_active = true,
        public bool $inherit_from_parent = false,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
    ) {}

    public function toArray(): array
    {
        return [
            'constraint_type' => $this->constraint_type,
            'constraint_name' => $this->name,
            'description' => $this->description,
            'constraint_config' => $this->config,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'user_id' => $this->user_id,
            'department_id' => $this->department_id,
            'branch_ids' => $this->branch_ids,
            'branch_locations' => $this->branch_locations,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'inherit_from_parent' => $this->inherit_from_parent,
            'start_date' => $this->effective_from,
            'end_date' => $this->effective_to,
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
}

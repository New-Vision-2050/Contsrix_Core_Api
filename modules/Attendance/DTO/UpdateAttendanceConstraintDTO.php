<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;
use Ramsey\Uuid\UuidInterface;
class UpdateAttendanceConstraintDTO
{
    public function __construct(
        public UuidInterface $updated_by,
        public ?string $constraint_type = null,
        public ?string $name = null,
        public ?string $notes = null,
        public ?array $config = null,
        public ?array $user_ids  = null,
        public ?array $department_ids = null,
        public ?array $branch_ids = null,
        public ?array $branch_locations = null,
        public ?int $priority = null,
        public ?bool $is_active = null,
        public ?bool $inherit_from_parent = null,
        public ?string $effective_from = null,
        public ?string $effective_to = null,
        public ?int $max_over_time = null,
    ) {}

    public function toArray(): array
    {
        $data = ['updated_by' => $this->updated_by];

        if ($this->constraint_type !== null) {
            $data['constraint_type'] = $this->constraint_type;
        }
        if ($this->name !== null) {
            $data['constraint_name'] = $this->name;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->config !== null) {
            $data['constraint_config'] = $this->config;
        }
        if ($this->user_ids !== null) {
            $data['user_ids'] = $this->user_ids;
        }
        if ($this->department_ids !== null) {
            $data['department_ids'] = $this->department_ids;
        }
        if ($this->branch_ids !== null) {
            $data['branch_ids'] = $this->branch_ids;
        }
        if ($this->branch_locations !== null) {
            $data['branch_locations'] = $this->branch_locations;
        }
        if ($this->priority !== null) {
            $data['priority'] = $this->priority;
        }
        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }
        if ($this->inherit_from_parent !== null) {
            $data['inherit_from_parent'] = $this->inherit_from_parent;
        }
        if ($this->effective_from !== null) {
            $data['effective_from'] = $this->effective_from;
        }
        if ($this->effective_to !== null) {
            $data['effective_to'] = $this->effective_to;
        }
        if ($this->max_over_time !== null) {
            $data['max_over_time'] = $this->max_over_time;
        }

        return $data;
    }

    public function getUpdatedBy(): UuidInterface
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getConfig(): ?array
    {
        return $this->config;
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

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function isInheritFromParent(): ?bool
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

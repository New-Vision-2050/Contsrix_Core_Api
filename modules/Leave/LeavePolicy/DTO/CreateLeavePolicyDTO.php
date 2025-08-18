<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateLeavePolicyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $total_days = null,
        public readonly ?string $day_type = null,
        public readonly bool $is_rollover_allowed = false,
        public readonly ?int $max_days_per_request = null,
        public readonly ?string $upgrade_condition = null,
        public readonly bool $is_allow_half_day = false,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'total_days' => $this->total_days,
            'day_type' => $this->day_type,
            'is_rollover_allowed' => $this->is_rollover_allowed,
            'max_days_per_request' => $this->max_days_per_request,
            'upgrade_condition' => $this->upgrade_condition,
            'is_allow_half_day' => $this->is_allow_half_day,
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTotalDays(): ?int
    {
        return $this->total_days;
    }

    public function getDayType(): ?string
    {
        return $this->day_type;
    }

    public function getIsRolloverAllowed(): bool
    {
        return $this->is_rollover_allowed;
    }

    public function getMaxDaysPerRequest(): ?int
    {
        return $this->max_days_per_request;
    }

    public function getUpgradeCondition(): ?string
    {
        return $this->upgrade_condition;
    }

    public function getIsAllowHalfDay(): bool
    {
        return $this->is_allow_half_day;
    }
}

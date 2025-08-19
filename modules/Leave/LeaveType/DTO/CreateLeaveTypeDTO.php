<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateLeaveTypeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly bool $is_payed = false,
        public readonly bool $is_deduct_from_balance = false,
        public readonly ?string $conditions = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_payed' => $this->is_payed,
            'is_deduct_from_balance' => $this->is_deduct_from_balance,
            'conditions' => $this->conditions,
            'company_id' => tenant('id'),
        ];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIsPayed(): bool
    {
        return $this->is_payed;
    }

    public function getIsDeductFromBalance(): bool
    {
        return $this->is_deduct_from_balance;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }
}

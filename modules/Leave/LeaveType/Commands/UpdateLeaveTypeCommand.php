<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateLeaveTypeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private bool $is_payed = false,
        private bool $is_deduct_from_balance = false,
        private ?string $conditions = null,
        private array $branch_ids = [],
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
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

    public function getBranchIds(): array
    {
        return $this->branch_ids;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_payed' => $this->is_payed,
            'is_deduct_from_balance' => $this->is_deduct_from_balance,
            'conditions' => $this->conditions,
            'branch_ids' => $this->branch_ids,
        ];
    }
}

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
        private bool $requires_attachment = false,
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

    public function getRequiresAttachment(): bool
    {
        return $this->requires_attachment;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'is_payed' => $this->is_payed,
            'is_deduct_from_balance' => $this->is_deduct_from_balance,
            'requires_attachment' => $this->requires_attachment,
        ];
    }
}

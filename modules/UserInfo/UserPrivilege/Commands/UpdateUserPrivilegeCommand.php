<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserPrivilegeCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $type_privilege_id,
        private ?string $type_allowance_id,
        private ?string $charge_amount,
        private ?string $description,
        private ?string $period_id,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return array_filter([
            'type_privilege' => $this->type_privilege_id,
            'type_allowance' => $this->type_allowance_id,
            'charge_amount' => $this->charge_amount,
            'description' => $this->description,
            'period' => $this->period_id
        ]);
    }
}

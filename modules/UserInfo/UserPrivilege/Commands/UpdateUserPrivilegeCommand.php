<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserPrivilegeCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $type_privilege,
        private string $type_allowance,
        private string $rate,
        private string $description,
        private string $period,
        private string $insurance_company,
        private string $insurance_number,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }


    public function toArray(): array
    {
        return array_filter([
            'type_privilege' => $this->type_privilege,
            'type_allowance' => $this->type_allowance,
            'rate' => $this->rate,
            'description' => $this->description,
            'period' => $this->period,
            'insurance_company' => $this->insurance_company,
            'insurance_number' => $this->insurance_number,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateSalaryTypeDTO
{
    public function __construct(
        public string $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}

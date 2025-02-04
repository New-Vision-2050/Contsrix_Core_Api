<?php

declare(strict_types=1);

namespace Modules\CompanyUser\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyUserDTO
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

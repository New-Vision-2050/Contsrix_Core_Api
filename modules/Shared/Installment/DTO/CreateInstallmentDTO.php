<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateInstallmentDTO
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

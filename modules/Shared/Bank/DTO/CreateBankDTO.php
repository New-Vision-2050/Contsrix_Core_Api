<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBankDTO
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

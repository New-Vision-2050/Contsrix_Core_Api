<?php

declare(strict_types=1);

namespace Modules\AdminRequest\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAdminRequestDTO
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

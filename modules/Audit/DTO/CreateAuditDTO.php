<?php

declare(strict_types=1);

namespace Modules\Audit\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAuditDTO
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

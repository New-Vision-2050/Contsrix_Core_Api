<?php

declare(strict_types=1);

namespace Modules\ActivityLog\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateActivityLogDTO
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

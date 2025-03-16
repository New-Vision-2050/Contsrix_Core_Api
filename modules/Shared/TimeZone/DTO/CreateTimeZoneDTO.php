<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTimeZoneDTO
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

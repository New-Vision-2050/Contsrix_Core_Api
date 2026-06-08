<?php

declare(strict_types=1);

namespace Modules\Shared/Process\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateShared/ProcessDTO
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

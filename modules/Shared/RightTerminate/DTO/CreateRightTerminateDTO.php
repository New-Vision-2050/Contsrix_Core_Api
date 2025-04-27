<?php

declare(strict_types=1);

namespace Modules\Shared\RightTerminate\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateRightTerminateDTO
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

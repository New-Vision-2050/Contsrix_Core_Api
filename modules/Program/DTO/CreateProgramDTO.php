<?php

declare(strict_types=1);

namespace Modules\Program\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProgramDTO
{
    public function __construct(
        public array $name,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}

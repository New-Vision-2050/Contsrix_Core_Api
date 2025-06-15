<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProfessionalBodieDTO
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

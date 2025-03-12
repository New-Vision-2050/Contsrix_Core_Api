<?php

declare(strict_types=1);

namespace Modules\Shared\Language\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateLanguageDTO
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

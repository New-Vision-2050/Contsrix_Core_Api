<?php

declare(strict_types=1);

namespace Modules\Country\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCountryDTO
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

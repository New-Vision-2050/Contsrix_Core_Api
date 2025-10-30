<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Home\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateHomeDTO
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

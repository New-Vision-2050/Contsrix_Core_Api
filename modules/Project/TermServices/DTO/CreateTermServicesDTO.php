<?php

declare(strict_types=1);

namespace Modules\Project\TermServices\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTermServicesDTO
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

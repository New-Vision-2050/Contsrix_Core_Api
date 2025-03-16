<?php

declare(strict_types=1);

namespace Modules\JobTitle\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateJobTitleDTO
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

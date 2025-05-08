<?php

declare(strict_types=1);

namespace Modules\Shared\TypeWorkingHour\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateTypeWorkingHourDTO
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

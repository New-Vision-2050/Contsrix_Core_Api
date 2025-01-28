<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateRegistrationTypeDTO
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

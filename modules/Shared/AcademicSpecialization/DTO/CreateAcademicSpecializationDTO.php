<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateAcademicSpecializationDTO
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

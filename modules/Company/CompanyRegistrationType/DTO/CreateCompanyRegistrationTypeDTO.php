<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyRegistrationTypeDTO
{
    public function __construct(
        public string $name,
        public string $type,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type
        ];
    }
}

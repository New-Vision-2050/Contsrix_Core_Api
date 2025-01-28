<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyRegistrationTypeDTO
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

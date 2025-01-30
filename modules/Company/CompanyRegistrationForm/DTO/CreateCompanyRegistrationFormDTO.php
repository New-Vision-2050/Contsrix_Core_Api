<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyRegistrationFormDTO
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

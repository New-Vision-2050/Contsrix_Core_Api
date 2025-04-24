<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyTypeDTO
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

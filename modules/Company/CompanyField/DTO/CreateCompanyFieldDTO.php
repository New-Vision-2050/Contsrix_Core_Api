<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyFieldDTO
{
    public function __construct(
        public string $name,
        public string $description
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}

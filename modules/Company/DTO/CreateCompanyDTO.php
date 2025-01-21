<?php

declare(strict_types=1);

namespace Modules\Company\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyDTO
{
    public function __construct(
        public string $name,
        private string $email,
        private string $phone,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ];
    }
}

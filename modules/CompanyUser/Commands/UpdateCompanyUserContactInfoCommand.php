<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyUserContactInfoCommand
{
    public function __construct(
        public string $email,
        public string $phone,
    ) {
    }


    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'phone' => $this->phone,

        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateCompanyAccessProgramDTO
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

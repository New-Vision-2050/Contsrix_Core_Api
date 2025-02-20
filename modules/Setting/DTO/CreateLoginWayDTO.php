<?php

declare(strict_types=1);

namespace Modules\Setting\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateLoginWayDTO
{
    public function __construct(
        public string $name,
        public array $loginOptions,
        public UuidInterface $companyId

    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'login_options' => $this->loginOptions,
            'company_id' => $this->companyId
        ];
    }
}

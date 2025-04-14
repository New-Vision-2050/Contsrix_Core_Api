<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserRelativeDTO
{
    public function __construct(
        public string $name,
        public string $company_id',
        public string $global_id,
        public string $marital_status,
        public string $relationship,
        public string $phone,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company_id',
            'global_id',
            'marital_status',
            'relationship',
            'phone',
        ];
    }
}

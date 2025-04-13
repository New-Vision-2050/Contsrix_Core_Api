<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateUserRelativeDTO
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

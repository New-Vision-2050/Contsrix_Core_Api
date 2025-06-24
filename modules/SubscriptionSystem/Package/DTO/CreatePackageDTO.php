<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\DTO;

use Ramsey\Uuid\UuidInterface;

class CreatePackageDTO
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

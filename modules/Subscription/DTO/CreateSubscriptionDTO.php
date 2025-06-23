<?php

declare(strict_types=1);

namespace Modules\Subscription\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateSubscriptionDTO
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

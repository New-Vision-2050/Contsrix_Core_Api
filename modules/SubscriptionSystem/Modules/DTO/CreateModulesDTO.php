<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateModulesDTO
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

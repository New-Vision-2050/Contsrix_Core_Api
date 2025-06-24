<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFeatureDTO
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

<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateModuleDTO
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

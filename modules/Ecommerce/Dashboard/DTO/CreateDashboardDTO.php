<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateDashboardDTO
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

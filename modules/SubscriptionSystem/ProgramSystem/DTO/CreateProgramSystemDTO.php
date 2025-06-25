<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateProgramSystemDTO
{
    public function __construct(
        public array $name,
        public array $features,
        public array $companyFields = [],
        public array $businessTypes = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}

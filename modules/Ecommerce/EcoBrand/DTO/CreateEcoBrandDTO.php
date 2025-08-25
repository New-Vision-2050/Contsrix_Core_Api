<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateEcoBrandDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public array $name,
        public ?array $description
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'name' => $this->name,
            'description' => $this->description
        ];
    }
}

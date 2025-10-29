<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFeatureDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $title,
        public string $description,
        public string $type,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'is_active' => $this->isActive,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBannerDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public string $url,
        public string $type,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'url' => $this->url,
            'type' => $this->type,
            'is_active' => $this->isActive,
        ];
    }
}

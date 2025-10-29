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
        public ?string $title = null,
        public ?string $description = null,
        public int $isActive = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'url' => $this->url,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateBannerDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public ?UuidInterface $settingPageId,
        public string $url,
        public int $isActive = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'setting_page_id' => $this->settingPageId?->toString(),
            'url' => $this->url,
            'is_active' => $this->isActive,
        ];
    }
}
